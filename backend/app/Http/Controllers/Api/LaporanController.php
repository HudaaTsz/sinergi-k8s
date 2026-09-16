<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KategoriKas;
use App\Models\Transaksi;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanController extends Controller
{
    /**
     * Siapkan data laporan bulanan (dipakai bareng oleh export Excel & PDF)
     * supaya tidak duplikasi logic.
     */
    protected function ambilDataLaporan(int $bulan, int $tahun): array
    {
        $tanggalAwal = Carbon::create($tahun, $bulan, 1);
        $jumlahHari = $tanggalAwal->daysInMonth;

        // Saldo awal = akumulasi SEMUA transaksi disetujui sebelum bulan ini
        $saldoAwal = (float) (Transaksi::where('status', 'disetujui')
            ->where('created_at', '<', $tanggalAwal->copy()->startOfMonth())
            ->selectRaw("SUM(CASE WHEN jenis = 'pemasukan' THEN jumlah ELSE -jumlah END) as saldo")
            ->value('saldo') ?? 0);

        $kategoriPemasukan = KategoriKas::whereIn('tipe', ['pemasukan', 'keduanya'])
            ->orderBy('nama')->get();

        $kategoriPengeluaran = KategoriKas::whereIn('tipe', ['pengeluaran', 'keduanya'])
            ->orderBy('nama')->get();

        $transaksiBulanIni = Transaksi::where('status', 'disetujui')
            ->whereMonth('created_at', $bulan)->whereYear('created_at', $tahun)
            ->get();

        $barisPemasukan = [];
        $barisPengeluaran = [];
        $totalPerKategoriPemasukan = array_fill(0, count($kategoriPemasukan), 0);
        $totalPerKategoriPengeluaran = array_fill(0, count($kategoriPengeluaran), 0);
        $totalMasuk = 0;
        $totalKeluar = 0;

        for ($tgl = 1; $tgl <= $jumlahHari; $tgl++) {
            $transaksiHariIni = $transaksiBulanIni->filter(fn ($t) => $t->created_at->day === $tgl);
            $labelTanggal = $tgl . ' ' . $tanggalAwal->translatedFormat('F Y');

            // ---- Baris untuk Tabel Pemasukan ----
            $kategoriHariIniMasuk = [];
            $masukHariIni = 0;
            foreach ($kategoriPemasukan as $i => $k) {
                $nilai = (float) $transaksiHariIni->where('jenis', 'pemasukan')->where('kategori_id', $k->id)->sum('jumlah');
                $kategoriHariIniMasuk[] = $nilai;
                $totalPerKategoriPemasukan[$i] += $nilai;
                $masukHariIni += $nilai;
            }
            $barisPemasukan[] = [
                'tanggal' => $labelTanggal,
                'kategori' => $kategoriHariIniMasuk,
                'total' => $masukHariIni,
            ];
            $totalMasuk += $masukHariIni;

            // ---- Baris untuk Tabel Pengeluaran ----
            $kategoriHariIniKeluar = [];
            $keluarHariIni = 0;
            foreach ($kategoriPengeluaran as $i => $k) {
                $nilai = (float) $transaksiHariIni->where('jenis', 'pengeluaran')->where('kategori_id', $k->id)->sum('jumlah');
                $kategoriHariIniKeluar[] = $nilai;
                $totalPerKategoriPengeluaran[$i] += $nilai;
                $keluarHariIni += $nilai;
            }
            $barisPengeluaran[] = [
                'tanggal' => $labelTanggal,
                'kategori' => $kategoriHariIniKeluar,
                'total' => $keluarHariIni,
            ];
            $totalKeluar += $keluarHariIni;
        }

        $saldoAkhir = $saldoAwal + $totalMasuk - $totalKeluar;

        return [
            'tanggalAwal' => $tanggalAwal,
            'kategoriPemasukan' => $kategoriPemasukan,
            'kategoriPengeluaran' => $kategoriPengeluaran,
            'barisPemasukan' => $barisPemasukan,
            'barisPengeluaran' => $barisPengeluaran,
            'totalPerKategoriPemasukan' => $totalPerKategoriPemasukan,
            'totalPerKategoriPengeluaran' => $totalPerKategoriPengeluaran,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => $totalKeluar,
            'saldoAwal' => $saldoAwal,
            'saldoAkhir' => $saldoAkhir,
        ];
    }

    public function exportBulanan(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);
        $d = $this->ambilDataLaporan($bulan, $tahun);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($d['tanggalAwal']->translatedFormat('F Y'), 0, 31));

        $baris = 1;
        $sheet->setCellValue('A' . $baris, 'LAPORAN KAS - ' . strtoupper($d['tanggalAwal']->translatedFormat('F Y')));
        $sheet->getStyle('A' . $baris)->getFont()->setBold(true)->setSize(14);
        $baris += 2;

        // TABEL 1: PEMASUKAN
        $baris = $this->tulisTabel(
            $sheet, $baris, 'TABEL 1 - PEMASUKAN',
            $d['kategoriPemasukan'], $d['barisPemasukan'], $d['totalPerKategoriPemasukan'], $d['totalMasuk'],
            '4F46E5'
        );
        $baris += 2;

        // TABEL 2: PENGELUARAN
        $baris = $this->tulisTabel(
            $sheet, $baris, 'TABEL 2 - PENGELUARAN',
            $d['kategoriPengeluaran'], $d['barisPengeluaran'], $d['totalPerKategoriPengeluaran'], $d['totalKeluar'],
            'DC2626'
        );
        $baris += 2;

        // TABEL 3: RINGKASAN
        $sheet->setCellValue('A' . $baris, 'TABEL 3 - RINGKASAN');
        $sheet->getStyle('A' . $baris)->getFont()->setBold(true)->setSize(12);
        $baris++;

        $ringkasan = [
            ['Saldo Awal (Bulan Sebelumnya)', $d['saldoAwal']],
            ['Total Pemasukan', $d['totalMasuk']],
            ['Total Pengeluaran', $d['totalKeluar']],
            ['Saldo Akhir', $d['saldoAkhir']],
        ];
        foreach ($ringkasan as [$label, $nilai]) {
            $sheet->setCellValue('A' . $baris, $label);
            $sheet->setCellValue('B' . $baris, $nilai);
            if ($label === 'Saldo Akhir') {
                $sheet->getStyle("A{$baris}:B{$baris}")->getFont()->setBold(true);
                $sheet->getStyle("A{$baris}:B{$baris}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
            }
            $baris++;
        }

        foreach (range('A', 'Z') as $kolom) {
            $sheet->getColumnDimension($kolom)->setAutoSize(true);
        }

        $namaFile = 'Laporan-Kas-' . $d['tanggalAwal']->translatedFormat('F-Y') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $namaFile, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function exportBulananPdf(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);
        $d = $this->ambilDataLaporan($bulan, $tahun);

        $html = view('laporan.bulanan-pdf', $d)->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');

        $namaFile = 'Laporan-Kas-' . $d['tanggalAwal']->translatedFormat('F-Y') . '.pdf';

        return $pdf->download($namaFile);
    }

    /**
     * Helper: tulis satu tabel (pemasukan atau pengeluaran) ke sheet Excel.
     * Return: nomor baris berikutnya yang masih kosong (untuk lanjut nulis tabel selanjutnya).
     */
    protected function tulisTabel($sheet, int $baris, string $judulTabel, $kategoriList, array $dataBaris, array $totalPerKategori, float $totalKeseluruhan, string $warna): int
    {
        $sheet->setCellValue('A' . $baris, $judulTabel);
        $sheet->getStyle('A' . $baris)->getFont()->setBold(true)->setSize(12);
        $baris++;

        $judulKolom = ['Tanggal'];
        foreach ($kategoriList as $k) $judulKolom[] = $k->nama;
        $judulKolom[] = 'Total';
        $jumlahKolom = count($judulKolom);
        $kolomTerakhir = Coordinate::stringFromColumnIndex($jumlahKolom);

        foreach ($judulKolom as $i => $judul) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $baris, $judul);
        }
        $sheet->getStyle("A{$baris}:{$kolomTerakhir}{$baris}")->getFont()->setBold(true);
        $sheet->getStyle("A{$baris}:{$kolomTerakhir}{$baris}")->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$baris}:{$kolomTerakhir}{$baris}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($warna);
        $baris++;

        foreach ($dataBaris as $row) {
            $sheet->setCellValue('A' . $baris, $row['tanggal']);
            foreach ($row['kategori'] as $i => $nilai) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 2) . $baris, $nilai);
            }
            $sheet->setCellValue($kolomTerakhir . $baris, $row['total']);
            $baris++;
        }

        $sheet->setCellValue('A' . $baris, 'TOTAL');
        foreach ($totalPerKategori as $i => $t) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 2) . $baris, $t);
        }
        $sheet->setCellValue($kolomTerakhir . $baris, $totalKeseluruhan);
        $sheet->getStyle("A{$baris}:{$kolomTerakhir}{$baris}")->getFont()->setBold(true);
        $sheet->getStyle("A{$baris}:{$kolomTerakhir}{$baris}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
        $baris++;

        return $baris;
    }
}