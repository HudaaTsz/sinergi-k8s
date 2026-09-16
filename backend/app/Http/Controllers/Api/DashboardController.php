<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DompetKas;
use App\Models\Transaksi;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $bulanIni = now()->month;
        $tahunIni = now()->year;
        $bulanLalu = now()->subMonth();

        // Kolom tanggal efektif: pengeluaran pakai tanggal disetujui_bendahara_at
        // (saat saldo benar-benar berkurang), fallback ke created_at kalau null
        // (untuk transaksi lama / auto-approved yang tidak lewat approval berlapis).
        $tglEfektifPengeluaran = "COALESCE(disetujui_bendahara_at, created_at)";

        $pemasukanBulanIni = Transaksi::where('jenis', 'pemasukan')
            ->where('status', 'disetujui')
            ->whereMonth('created_at', $bulanIni)->whereYear('created_at', $tahunIni)
            ->sum('jumlah');

        $pengeluaranBulanIni = Transaksi::where('jenis', 'pengeluaran')
            ->where('status', 'disetujui')
            ->whereRaw("EXTRACT(MONTH FROM {$tglEfektifPengeluaran}) = ?", [$bulanIni])
            ->whereRaw("EXTRACT(YEAR FROM {$tglEfektifPengeluaran}) = ?", [$tahunIni])
            ->sum('jumlah');

        $pengeluaranBulanLalu = Transaksi::where('jenis', 'pengeluaran')
            ->where('status', 'disetujui')
            ->whereRaw("EXTRACT(MONTH FROM {$tglEfektifPengeluaran}) = ?", [$bulanLalu->month])
            ->whereRaw("EXTRACT(YEAR FROM {$tglEfektifPengeluaran}) = ?", [$bulanLalu->year])
            ->sum('jumlah');

        $pemasukanBulanLalu = Transaksi::where('jenis', 'pemasukan')
            ->where('status', 'disetujui')
            ->whereMonth('created_at', $bulanLalu->month)->whereYear('created_at', $bulanLalu->year)
            ->sum('jumlah');

        // grafik 6 bulan terakhir (pemasukan vs pengeluaran)
        $grafik = collect(range(5, 0))->map(function ($i) use ($tglEfektifPengeluaran) {
            $bulan = now()->subMonths($i);
            return [
                'bulan' => $bulan->translatedFormat('M Y'),
                'pemasukan' => (float) Transaksi::where('jenis', 'pemasukan')->where('status', 'disetujui')
                    ->whereMonth('created_at', $bulan->month)->whereYear('created_at', $bulan->year)->sum('jumlah'),
                'pengeluaran' => (float) Transaksi::where('jenis', 'pengeluaran')->where('status', 'disetujui')
                    ->whereRaw("EXTRACT(MONTH FROM {$tglEfektifPengeluaran}) = ?", [$bulan->month])
                    ->whereRaw("EXTRACT(YEAR FROM {$tglEfektifPengeluaran}) = ?", [$bulan->year])
                    ->sum('jumlah'),
            ];
        });

        $pengeluaranPerKategori = Transaksi::query()
            ->join('kategori_kas', 'kategori_kas.id', '=', 'transaksi.kategori_id')
            ->where('transaksi.jenis', 'pengeluaran')
            ->where('transaksi.status', 'disetujui')
            ->whereRaw("EXTRACT(MONTH FROM COALESCE(transaksi.disetujui_bendahara_at, transaksi.created_at)) = ?", [$bulanIni])
            ->whereRaw("EXTRACT(YEAR FROM COALESCE(transaksi.disetujui_bendahara_at, transaksi.created_at)) = ?", [$tahunIni])
            ->selectRaw('kategori_kas.nama as kategori, SUM(transaksi.jumlah) as total')
            ->groupBy('kategori_kas.nama')
            ->orderByDesc('total')
            ->get();

        $pemasukanPerKategori = Transaksi::query()
            ->join('kategori_kas', 'kategori_kas.id', '=', 'transaksi.kategori_id')
            ->where('transaksi.jenis', 'pemasukan')
            ->where('transaksi.status', 'disetujui')
            ->whereMonth('transaksi.created_at', $bulanIni)->whereYear('transaksi.created_at', $tahunIni)
            ->selectRaw('kategori_kas.nama as kategori, SUM(transaksi.jumlah) as total')
            ->groupBy('kategori_kas.nama')
            ->orderByDesc('total')
            ->get();

        $menungguApproval = Transaksi::whereIn('status', ['menunggu_ketua', 'menunggu_bendahara'])->count();

        return response()->json([
            'total_saldo' => (float) DompetKas::sum('saldo'),
            'pemasukan_bulan_ini' => (float) $pemasukanBulanIni,
            'pengeluaran_bulan_ini' => (float) $pengeluaranBulanIni,
            'trend_pemasukan' => $this->hitungTrend($pemasukanBulanIni, $pemasukanBulanLalu),
            'trend_pengeluaran' => $this->hitungTrend($pengeluaranBulanIni, $pengeluaranBulanLalu),
            'menunggu_approval' => $menungguApproval,
            'grafik' => $grafik,
            'pengeluaran_per_kategori' => $pengeluaranPerKategori,
            'pemasukan_per_kategori' => $pemasukanPerKategori,
            'transaksi_terbaru' => Transaksi::with(['kategori', 'pembuat'])
                ->latest()->limit(8)->get(),
        ]);
    }

    protected function hitungTrend($sekarang, $lalu): ?float
    {
        if (!$lalu || $lalu == 0) return null;
        return round((($sekarang - $lalu) / $lalu) * 100, 1);
    }
}