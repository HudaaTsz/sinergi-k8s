<?php

use App\Http\Controllers\Api\AIChatController;
use App\Http\Controllers\Api\AnggotaController;
use App\Http\Controllers\Api\AnggotaIuranController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IuranController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\PengajuanDanaController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\KategoriKasController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/me/password', [ProfileController::class, 'updatePassword']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Master data (dropdown form transaksi, dll)
    Route::get('/master/kategori-kas', [MasterDataController::class, 'kategoriKas']);
    Route::post('/kategori-kas', [KategoriKasController::class, 'store'])
        ->middleware('role:Ketua|Bendahara|Super Admin');
    Route::get('/master/dompet-kas', [MasterDataController::class, 'dompetKas']);

    // Anggota (akun login sistem / "Akun Intern") — hanya pengurus yang boleh lihat & kelola
    Route::get('/anggota', [AnggotaController::class, 'index'])
        ->middleware('role:Super Admin');
    Route::post('/anggota', [AnggotaController::class, 'store'])->middleware('role:Super Admin|Sekretaris');
    Route::put('/anggota/{anggota}', [AnggotaController::class, 'update'])
        ->middleware('role:Super Admin|Sekretaris');
    Route::post('/anggota/{anggota}/foto', [AnggotaController::class, 'uploadFoto'])
        ->middleware('role:Super Admin|Sekretaris');
    Route::post('/anggota/{anggota}/reset-password', [AnggotaController::class, 'resetPassword'])
        ->middleware('role:Super Admin');

    // Anggota Iuran ("Akun Extern") — SEMUA role login (termasuk Anggota) boleh lihat.
    // Tambah/edit/hapus dibatasi ke Bendahara/Super Admin.
    Route::get('/anggota-iuran', [AnggotaIuranController::class, 'index']);
    Route::post('/anggota-iuran', [AnggotaIuranController::class, 'store'])
        ->middleware('role:Bendahara|Super Admin');
    Route::put('/anggota-iuran/{anggotaIuran}', [AnggotaIuranController::class, 'update'])
        ->middleware('role:Bendahara|Super Admin');
    Route::delete('/anggota-iuran/{anggotaIuran}', [AnggotaIuranController::class, 'destroy'])
        ->middleware('role:Bendahara|Super Admin');

    // Kas & Transaksi — Anggota hanya boleh LIHAT (read-only), tidak boleh input/aksi
    Route::get('/transaksi', [TransaksiController::class, 'index']);
    Route::post('/transaksi', [TransaksiController::class, 'store'])
        ->middleware('role:Ketua|Bendahara|Super Admin');
    Route::post('/transaksi/{transaksi}/approve-ketua', [TransaksiController::class, 'approveKetua'])
        ->middleware('role:Ketua|Super Admin');
    Route::post('/transaksi/{transaksi}/approve-bendahara', [TransaksiController::class, 'approveBendahara'])
        ->middleware('role:Bendahara|Super Admin');
    Route::post('/transaksi/{transaksi}/tolak', [TransaksiController::class, 'tolak'])
        ->middleware('role:Ketua|Bendahara|Super Admin');

    // Iuran — Anggota hanya boleh LIHAT (read-only), tidak boleh input/aksi
    Route::get('/iuran/periode', [IuranController::class, 'indexPeriode']);
    Route::post('/iuran/periode', [IuranController::class, 'storePeriode'])->middleware('role:Bendahara|Super Admin');
    Route::get('/iuran/periode/{periode}/anggota', [IuranController::class, 'daftarAnggotaStatus']);
    Route::get('/iuran/periode/{periode}/belum-lunas', [IuranController::class, 'belumLunas']);
    Route::post('/iuran/bayar', [IuranController::class, 'bayar'])->middleware('role:Bendahara|Super Admin');
    Route::post('/iuran/{pembayaran}/batalkan-terakhir', [IuranController::class, 'batalkanPembayaranTerakhir'])->middleware('role:Bendahara|Super Admin');

    // Pengajuan Dana
    Route::get('/pengajuan-dana', [PengajuanDanaController::class, 'index']);
    Route::post('/pengajuan-dana', [PengajuanDanaController::class, 'store']);
    Route::post('/pengajuan-dana/{pengajuan}/keputusan-ketua', [PengajuanDanaController::class, 'keputusanKetua'])
        ->middleware('role:Bendahara|Ketua|Super Admin');
    Route::post('/pengajuan-dana/{pengajuan}/keputusan-bendahara', [PengajuanDanaController::class, 'keputusanBendahara'])
        ->middleware('role:Bendahara|Super Admin');
    Route::post('/pengajuan-dana/{pengajuan}/cairkan', [PengajuanDanaController::class, 'cairkan'])
        ->middleware('role:Bendahara|Super Admin');

    // Laporan
    Route::get('/laporan/export', [LaporanController::class, 'exportBulanan'])
        ->middleware('role:Bendahara|Ketua|Super Admin|Auditor');
    Route::get('/laporan/export-pdf', [LaporanController::class, 'exportBulananPdf'])
        ->middleware('role:Bendahara|Ketua|Super Admin|Auditor');

    // AI Assistant
    Route::post('/ai/chat', [AIChatController::class, 'chat']);
    Route::get('/ai/riwayat', [AIChatController::class, 'riwayat']);
});