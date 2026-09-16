<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnggotaIuran;
use App\Models\DompetKas;
use App\Models\IuranPeriode;
use App\Models\KategoriKas;
use App\Models\PembayaranIuran;
use App\Models\PembayaranIuranLog;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IuranController extends Controller
{
    /**
     * Bendahara membuat periode iuran baru -> generate baris pembayaran utk
     * semua anggota aktif. Kalau anggota punya kredit dari kelebihan bayar
     * sebelumnya, kredit itu langsung dipakai mengurangi tagihan periode ini.
     */
    public function storePeriode(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string',
            'besaran' => 'required|numeric|min:0',
            'jatuh_tempo' => 'required|date',
        ]);

        $periode = DB::transaction(function () use ($data, $request) {
            $periode = IuranPeriode::create($data);

            $anggotaAktif = AnggotaIuran::where('status', 'aktif')->get();

            foreach ($anggotaAktif as $anggota) {
                if (PembayaranIuran::where('anggota_id', $anggota->id)->where('iuran_periode_id', $periode->id)->exists()) {
                    continue;
                }

                $besaran = (float) $periode->besaran;
                $kredit = (float) $anggota->saldo_kredit;
                $kreditTerpakai = min($kredit, $besaran);

                $pembayaran = PembayaranIuran::create([
                    'anggota_id' => $anggota->id,
                    'iuran_periode_id' => $periode->id,
                    'tagihan' => $besaran,
                    'total_dibayar' => $kreditTerpakai,
                    'kredit_terpakai' => $kreditTerpakai,
                    'status' => $kreditTerpakai >= $besaran ? 'lunas' : ($kreditTerpakai > 0 ? 'kurang_bayar' : 'belum_lunas'),
                    'tanggal_bayar' => $kreditTerpakai > 0 ? now() : null,
                ]);

                if ($kreditTerpakai > 0) {
                    $anggota->decrement('saldo_kredit', $kreditTerpakai);

                    PembayaranIuranLog::create([
                        'pembayaran_iuran_id' => $pembayaran->id,
                        'nominal' => $kreditTerpakai,
                        'tanggal_bayar' => now(),
                        'transaksi_id' => null,
                        'dicatat_oleh' => $request->user()->id,
                    ]);
                }
            }

            return $periode;
        });

        return response()->json($periode, 201);
    }

    public function indexPeriode()
    {
        return IuranPeriode::withCount([
            'pembayaran as lunas_count' => fn ($q) => $q->where('status', 'lunas'),
            'pembayaran as belum_lunas_count' => fn ($q) => $q->where('status', 'belum_lunas'),
            'pembayaran as kurang_bayar_count' => fn ($q) => $q->where('status', 'kurang_bayar'),
        ])->latest('jatuh_tempo')->get();
    }

    /**
     * Daftar SEMUA anggota iuran aktif beserta status pembayaran untuk satu
     * periode — termasuk anggota yang baru ditambahkan setelah periode dibuat.
     */
    public function daftarAnggotaStatus(IuranPeriode $periode)
    {
        $anggota = AnggotaIuran::where('status', 'aktif')->orderBy('rt')->orderBy('nama')->get();
        $pembayaran = PembayaranIuran::where('iuran_periode_id', $periode->id)->get()->keyBy('anggota_id');

        return $anggota->map(function ($a) use ($pembayaran, $periode) {
            $p = $pembayaran->get($a->id);

            if ($p) {
                $tagihan = (float) $p->tagihan;
                $totalDibayar = (float) $p->total_dibayar;

                return [
                    'anggota_id' => $a->id,
                    'nama' => $a->nama,
                    'rt' => $a->rt,
                    'pembayaran_id' => $p->id,
                    'status' => $p->status,
                    'tagihan' => $tagihan,
                    'total_dibayar' => $totalDibayar,
                    'sisa_tagihan' => max($tagihan - $totalDibayar, 0),
                    'kelebihan' => max($totalDibayar - $tagihan, 0),
                    'tanggal_bayar' => $p->tanggal_bayar,
                ];
            }

            // Anggota baru, baris pembayaran belum ada -> preview tagihan dari kredit terkini
            $kredit = (float) $a->saldo_kredit;
            $besaran = (float) $periode->besaran;
            $totalDibayarPreview = min($kredit, $besaran);
            $tagihanSisa = $besaran - $totalDibayarPreview;

            return [
                'anggota_id' => $a->id,
                'nama' => $a->nama,
                'rt' => $a->rt,
                'pembayaran_id' => null,
                'status' => $tagihanSisa <= 0 ? 'lunas' : ($totalDibayarPreview > 0 ? 'kurang_bayar' : 'belum_lunas'),
                'tagihan' => $besaran,
                'total_dibayar' => $totalDibayarPreview,
                'sisa_tagihan' => max($tagihanSisa, 0),
                'kelebihan' => 0,
                'tanggal_bayar' => null,
            ];
        })->values();
    }

    /**
     * Proses pembayaran iuran dengan nominal bebas.
     * - total_dibayar SELALU bertambah PERSIS sejumlah nominal yang dibayar
     *   (tidak di-cap ke tagihan) — supaya tercatat sesuai uang aktual masuk.
     * - Kalau nominal > sisa tagihan periode ini, kelebihannya langsung
     *   dicoba dipakai buat periode-periode berikutnya yang SUDAH DIBUAT dan
     *   masih ada tagihan (diurutkan dari jatuh_tempo paling dekat). Sisa
     *   kredit yang belum terpakai (karena periode berikutnya belum dibuat)
     *   disimpan di saldo_kredit anggota.
     */
    public function bayar(Request $request)
    {
        $data = $request->validate([
            'anggota_id' => 'required|exists:anggota_iuran,id',
            'iuran_periode_id' => 'required|exists:iuran_periode,id',
            'nominal' => 'required|numeric|min:1',
            'dompet_kas_id' => 'required|exists:dompet_kas,id',
            'tanggal_bayar' => 'nullable|date',
        ]);

        $hasil = DB::transaction(function () use ($data, $request) {
            $anggota = AnggotaIuran::lockForUpdate()->findOrFail($data['anggota_id']);
            $periode = IuranPeriode::findOrFail($data['iuran_periode_id']);

            $pembayaran = PembayaranIuran::firstOrNew([
                'anggota_id' => $anggota->id,
                'iuran_periode_id' => $periode->id,
            ]);

            if (!$pembayaran->exists) {
                $pembayaran->tagihan = (float) $periode->besaran;
                $pembayaran->total_dibayar = 0;
                $pembayaran->kredit_terpakai = 0;
                $pembayaran->status = 'belum_lunas';
            }

            $nominal = (float) $data['nominal'];
            $sisaSebelum = max((float) $pembayaran->tagihan - (float) $pembayaran->total_dibayar, 0);
            $tanggalBayar = $data['tanggal_bayar'] ?? now()->toDateString();

            // total_dibayar bertambah PERSIS sejumlah nominal, tidak di-cap
            $pembayaran->total_dibayar = (float) $pembayaran->total_dibayar + $nominal;
            $pembayaran->status = $pembayaran->total_dibayar >= $pembayaran->tagihan ? 'lunas' : 'kurang_bayar';
            $pembayaran->tanggal_bayar = $tanggalBayar;
            $pembayaran->save();

            // Uang masuk ke saldo utama SEBESAR NOMINAL YANG DIBAYAR (uang riil, apa pun statusnya)
            $transaksi = new Transaksi([
                'kode' => 'TRX-' . now()->year . '-' . Str::padLeft((string) (Transaksi::count() + 1), 6, '0'),
                'dompet_kas_id' => $data['dompet_kas_id'],
                'kategori_id' => $this->kategoriIuranId(),
                'jenis' => 'pemasukan',
                'jumlah' => $nominal,
                'deskripsi' => 'Iuran ' . $periode->nama . ' - ' . $anggota->nama,
                'dibuat_oleh' => $request->user()->id,
                'status' => 'disetujui',
            ]);
            $transaksi->save();

            DompetKas::find($data['dompet_kas_id'])->increment('saldo', $nominal);

            PembayaranIuranLog::create([
                'pembayaran_iuran_id' => $pembayaran->id,
                'nominal' => $nominal,
                'tanggal_bayar' => $tanggalBayar,
                'transaksi_id' => $transaksi->id,
                'dicatat_oleh' => $request->user()->id,
            ]);

            // Kalau bayar lebih dari sisa tagihan periode ini -> kelebihan
            // langsung dijalankan ke periode-periode berikutnya yang sudah ada.
            if ($nominal > $sisaSebelum) {
                $kelebihan = $nominal - $sisaSebelum;
                $anggota->increment('saldo_kredit', $kelebihan);
                $this->terapkanKreditBerantai($anggota->fresh(), $periode, $request->user()->id);
            }

            return $pembayaran->fresh();
        });

        return response()->json($hasil->load('anggota', 'periode', 'logPembayaran'), 201);
    }

    /**
     * Terapkan saldo_kredit anggota ke periode-periode berikutnya yang SUDAH
     * DIBUAT (jatuh_tempo > periode saat ini) dan masih punya sisa tagihan,
     * diurutkan dari yang paling dekat jatuh temponya. Tidak membuat Transaksi
     * baru karena ini bukan uang baru, cuma pemindahan kredit yang sudah ada.
     */
    protected function terapkanKreditBerantai(AnggotaIuran $anggota, IuranPeriode $periodeSaatIni, ?int $dicatatOleh): void
    {
        while ((float) $anggota->saldo_kredit > 0) {
            $target = PembayaranIuran::where('pembayaran_iuran.anggota_id', $anggota->id)
                ->whereIn('pembayaran_iuran.status', ['belum_lunas', 'kurang_bayar'])
                ->whereColumn('pembayaran_iuran.total_dibayar', '<', 'pembayaran_iuran.tagihan')
                ->join('iuran_periode', 'iuran_periode.id', '=', 'pembayaran_iuran.iuran_periode_id')
                ->where('iuran_periode.jatuh_tempo', '>', $periodeSaatIni->jatuh_tempo)
                ->orderBy('iuran_periode.jatuh_tempo')
                ->select('pembayaran_iuran.*')
                ->first();

            if (!$target) {
                break; // tidak ada periode berikutnya yang bisa ditutup, sisa kredit menunggu periode baru dibuat
            }

            $sisa = (float) $target->tagihan - (float) $target->total_dibayar;
            $pakai = min((float) $anggota->saldo_kredit, $sisa);

            $target->total_dibayar = (float) $target->total_dibayar + $pakai;
            $target->kredit_terpakai = (float) $target->kredit_terpakai + $pakai;
            $target->status = $target->total_dibayar >= $target->tagihan ? 'lunas' : 'kurang_bayar';
            $target->tanggal_bayar = $target->tanggal_bayar ?? now();
            $target->save();

            $anggota->decrement('saldo_kredit', $pakai);
            $anggota->refresh();

            PembayaranIuranLog::create([
                'pembayaran_iuran_id' => $target->id,
                'nominal' => $pakai,
                'tanggal_bayar' => now(),
                'transaksi_id' => null,
                'dicatat_oleh' => $dicatatOleh,
            ]);
        }
    }

    /**
     * Batalkan RIWAYAT PEMBAYARAN TERAKHIR pada satu baris pembayaran.
     * - Kalau log itu pembayaran tunai (ada transaksi_id): balikkan saldo
     *   dompet & hapus transaksinya.
     * - Kalau log itu hasil penerapan kredit otomatis (transaksi_id null):
     *   kembalikan nominalnya jadi saldo_kredit anggota lagi (tidak ada kas
     *   yang perlu dibalikkan, karena ini bukan uang baru).
     *
     * CATATAN: kalau kredit dari pembayaran ini sudah sempat "menjalar" ke
     * periode-periode berikutnya, membatalkan baris asalnya TIDAK otomatis
     * menarik balik kredit yang sudah terpakai di periode lain. Untuk kasus
     * itu, batalkan dulu log di periode-periode berikutnya (yang transaksi_id
     * null) sebelum membatalkan pembayaran tunai aslinya.
     */
    public function batalkanPembayaranTerakhir(PembayaranIuran $pembayaran)
    {
        DB::transaction(function () use ($pembayaran) {
            $log = $pembayaran->logPembayaran()->latest('id')->first();
            abort_if(!$log, 404, 'Belum ada riwayat pembayaran untuk baris ini.');

            $anggota = $pembayaran->anggota;

            if ($log->transaksi_id === null) {
                // Hasil penerapan kredit otomatis -> kembalikan sebagai kredit
                $anggota->increment('saldo_kredit', $log->nominal);
            } else {
                // Pembayaran tunai asli -> balikkan saldo dompet & hapus transaksi
                $transaksi = Transaksi::find($log->transaksi_id);
                if ($transaksi) {
                    DompetKas::find($transaksi->dompet_kas_id)?->decrement('saldo', $transaksi->jumlah);
                    $transaksi->delete();
                }
            }

            $pembayaran->total_dibayar = max((float) $pembayaran->total_dibayar - (float) $log->nominal, 0);
            $pembayaran->kredit_terpakai = max((float) $pembayaran->kredit_terpakai - ($log->transaksi_id === null ? (float) $log->nominal : 0), 0);
            $pembayaran->status = $pembayaran->total_dibayar <= 0
                ? 'belum_lunas'
                : ($pembayaran->total_dibayar >= $pembayaran->tagihan ? 'lunas' : 'kurang_bayar');
            $pembayaran->tanggal_bayar = $pembayaran->total_dibayar <= 0 ? null : $pembayaran->tanggal_bayar;
            $pembayaran->save();

            $log->delete();
        });

        return response()->json($pembayaran->fresh(['anggota', 'periode', 'logPembayaran']));
    }

    public function belumLunas(IuranPeriode $periode)
    {
        return $periode->pembayaran()
            ->whereIn('status', ['belum_lunas', 'kurang_bayar'])
            ->with('anggota:id,nama,rt')
            ->get();
    }

    protected function kategoriIuranId(): int
    {
        return KategoriKas::where('nama', 'Iuran')->firstOrFail()->id;
    }
}