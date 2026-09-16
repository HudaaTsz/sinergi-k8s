<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: right; }
        th { color: #fff; text-align: center; }
        td:first-child, th:first-child { text-align: left; }
        .th-masuk { background-color: #4F46E5; }
        .th-keluar { background-color: #DC2626; }
        .total-row { font-weight: bold; background-color: #F3F4F6; }
        .ringkasan td:first-child { width: 250px; }
    </style>
</head>
<body>
    <h1>LAPORAN KAS - {{ strtoupper($tanggalAwal->translatedFormat('F Y')) }}</h1>

    <h2>Tabel 1 - Pemasukan</h2>
    <table>
        <thead>
            <tr>
                <th class="th-masuk">Tanggal</th>
                @foreach ($kategoriPemasukan as $k)
                    <th class="th-masuk">{{ $k->nama }}</th>
                @endforeach
                <th class="th-masuk">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($barisPemasukan as $row)
                <tr>
                    <td>{{ $row['tanggal'] }}</td>
                    @foreach ($row['kategori'] as $nilai)
                        <td>{{ number_format($nilai, 0, ',', '.') }}</td>
                    @endforeach
                    <td>{{ number_format($row['total'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>TOTAL</td>
                @foreach ($totalPerKategoriPemasukan as $t)
                    <td>{{ number_format($t, 0, ',', '.') }}</td>
                @endforeach
                <td>{{ number_format($totalMasuk, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Tabel 2 - Pengeluaran</h2>
    <table>
        <thead>
            <tr>
                <th class="th-keluar">Tanggal</th>
                @foreach ($kategoriPengeluaran as $k)
                    <th class="th-keluar">{{ $k->nama }}</th>
                @endforeach
                <th class="th-keluar">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($barisPengeluaran as $row)
                <tr>
                    <td>{{ $row['tanggal'] }}</td>
                    @foreach ($row['kategori'] as $nilai)
                        <td>{{ number_format($nilai, 0, ',', '.') }}</td>
                    @endforeach
                    <td>{{ number_format($row['total'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>TOTAL</td>
                @foreach ($totalPerKategoriPengeluaran as $t)
                    <td>{{ number_format($t, 0, ',', '.') }}</td>
                @endforeach
                <td>{{ number_format($totalKeluar, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Tabel 3 - Ringkasan</h2>
    <table class="ringkasan">
        <tr>
            <td>Saldo Awal (Bulan Sebelumnya)</td>
            <td>{{ number_format($saldoAwal, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Total Pemasukan</td>
            <td>{{ number_format($totalMasuk, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Total Pengeluaran</td>
            <td>{{ number_format($totalKeluar, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td>Saldo Akhir</td>
            <td>{{ number_format($saldoAkhir, 0, ',', '.') }}</td>
        </tr>
    </table>
</body>
</html>