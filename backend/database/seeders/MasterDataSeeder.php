<?php

namespace Database\Seeders;

use App\Models\DompetKas;
use App\Models\KategoriKas;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            ['nama' => 'Iuran', 'tipe' => 'pemasukan'],
            ['nama' => 'Donasi', 'tipe' => 'pemasukan'],
            ['nama' => 'Sponsor', 'tipe' => 'pemasukan'],
            ['nama' => 'Pembelian ATK', 'tipe' => 'pengeluaran'],
            ['nama' => 'Konsumsi', 'tipe' => 'pengeluaran'],
            ['nama' => 'Transportasi', 'tipe' => 'pengeluaran'],
            ['nama' => 'Kegiatan', 'tipe' => 'keduanya'],
        ];

        foreach ($kategori as $item) {
            KategoriKas::firstOrCreate(['nama' => $item['nama']], $item);
        }

        DompetKas::firstOrCreate(['nama' => 'Kas Umum'], ['nama' => 'Kas Umum', 'saldo' => 0]);
    }
}