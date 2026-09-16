<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DompetKas;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransaksiController extends Controller
{
    public function index(Request $request)
    {
        return Transaksi::with(['dompet', 'kategori', 'pembuat'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->jenis, fn ($q, $j) => $q->where('jenis', $j))
            ->latest()
            ->paginate(20);
    }

    /** Anggota/Bendahara input transaksi baru (pemasukan/pengeluaran/transfer). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'dompet_kas_id' => 'required|exists:dompet_kas,id',
            'kategori_id' => 'required|exists:kategori_kas,id',
            'jenis' => 'required|in:pemasukan,pengeluaran,transfer_masuk,transfer_keluar',
            'jumlah' => 'required|numeric|min:1',
            'deskripsi' => 'nullable|string',
            'bukti_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'dompet_tujuan_id' => 'nullable|exists:dompet_kas,id',
            'event_id' => 'nullable|exists:events,id',
        ]);

        // PENTING: pengeluaran/transfer-keluar tidak boleh melebihi saldo
        // dompet kas yang bersangkutan. Dicek terhadap saldo TERKINI
        // (bukan menunggu approval selesai) supaya tidak ada transaksi yang
        // lolos input padahal dananya sudah tidak cukup.
        if (in_array($data['jenis'], ['pengeluaran', 'transfer_keluar'])) {
            $dompet = DompetKas::find($data['dompet_kas_id']);

            if ($dompet && $data['jumlah'] > $dompet->saldo) {
                throw ValidationException::withMessages([
                    'jumlah' => [
                        'Jumlah melebihi sisa saldo kas "' . $dompet->nama . '" '
                        . '(saldo saat ini: Rp' . number_format($dompet->saldo, 0, ',', '.') . ').',
                    ],
                ]);
            }
        }

        if ($request->hasFile('bukti_file')) {
            $data['bukti_file'] = $request->file('bukti_file')->store('bukti-transaksi', 'public');
        }

        $data['kode'] = 'TRX-' . now()->year . '-' . Str::padLeft((string) (Transaksi::count() + 1), 6, '0');
        $data['dibuat_oleh'] = $request->user()->id;

        $transaksi = new Transaksi($data);

        // Jika pengeluaran kecil (<= Rp500.000), langsung disetujui otomatis.
        // Jika besar, wajib approval berlapis: Ketua -> Bendahara.
        $transaksi->status = $transaksi->butuhApprovalBerlapis() ? 'menunggu_ketua' : 'disetujui';
        $transaksi->save();

        if ($transaksi->status === 'disetujui') {
            $this->terapkanKeSaldo($transaksi);
        }

        return response()->json($transaksi->load(['dompet', 'kategori']), 201);
    }

    /** Approval tahap 1 oleh Ketua. */
    public function approveKetua(Request $request, Transaksi $transaksi)
    {
        abort_unless($request->user()->hasRole(['Ketua', 'Super Admin']), 403);
        abort_unless($transaksi->status === 'menunggu_ketua', 422, 'Transaksi tidak dalam status menunggu Ketua.');

        $transaksi->update([
            'status' => 'menunggu_bendahara',
            'disetujui_ketua_at' => now(),
            'disetujui_ketua_oleh' => $request->user()->id,
        ]);

        return response()->json($transaksi);
    }

    /** Approval tahap 2 oleh Bendahara -> masuk laporan & update saldo. */
    public function approveBendahara(Request $request, Transaksi $transaksi)
    {
        abort_unless($request->user()->hasRole(['Bendahara', 'Super Admin']), 403);
        abort_unless($transaksi->status === 'menunggu_bendahara', 422, 'Transaksi tidak dalam status menunggu Bendahara.');

        // Cek ulang saldo di titik approval terakhir (bisa saja saldo sudah
        // berkurang karena transaksi lain yang disetujui lebih dulu).
        if (in_array($transaksi->jenis, ['pengeluaran', 'transfer_keluar'])) {
            $dompet = DompetKas::find($transaksi->dompet_kas_id);
            if ($dompet && $transaksi->jumlah > $dompet->saldo) {
                throw ValidationException::withMessages([
                    'jumlah' => [
                        'Tidak bisa disetujui: saldo kas "' . $dompet->nama . '" saat ini '
                        . 'Rp' . number_format($dompet->saldo, 0, ',', '.') . ', kurang dari jumlah transaksi.',
                    ],
                ]);
            }
        }

        $transaksi->update([
            'status' => 'disetujui',
            'disetujui_bendahara_at' => now(),
            'disetujui_bendahara_oleh' => $request->user()->id,
        ]);

        $this->terapkanKeSaldo($transaksi);

        return response()->json($transaksi);
    }

    public function tolak(Request $request, Transaksi $transaksi)
    {
        abort_unless($request->user()->hasRole(['Ketua', 'Bendahara', 'Super Admin']), 403);

        $transaksi->update([
            'status' => 'ditolak',
            'catatan_penolakan' => $request->input('catatan'),
        ]);

        return response()->json($transaksi);
    }

    protected function terapkanKeSaldo(Transaksi $transaksi): void
    {
        $dompet = DompetKas::find($transaksi->dompet_kas_id);

        match ($transaksi->jenis) {
            'pemasukan', 'transfer_masuk' => $dompet->increment('saldo', $transaksi->jumlah),
            'pengeluaran', 'transfer_keluar' => $dompet->decrement('saldo', $transaksi->jumlah),
            default => null,
        };

        if ($transaksi->jenis === 'transfer_keluar' && $transaksi->dompet_tujuan_id) {
            DompetKas::find($transaksi->dompet_tujuan_id)?->increment('saldo', $transaksi->jumlah);
        }
    }
}