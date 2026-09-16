<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_kas', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // Iuran, Donasi, Sponsor, ATK, Konsumsi, Transportasi, Kegiatan
            $table->enum('tipe', ['pemasukan', 'pengeluaran', 'keduanya'])->default('keduanya');
            $table->timestamps();
        });

        Schema::create('dompet_kas', function (Blueprint $table) {
            // mendukung "Transfer antar kas" — misal Kas Umum, Kas Divisi A, Kas Event X
            $table->id();
            $table->string('nama');
            $table->decimal('saldo', 15, 2)->default(0);
            $table->foreignId('divisi_id')->nullable();
            $table->timestamps();
        });

        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique(); // TRX-2026-000123
            $table->foreignId('dompet_kas_id')->constrained('dompet_kas');
            $table->foreignId('kategori_id')->constrained('kategori_kas');
            $table->enum('jenis', ['pemasukan', 'pengeluaran', 'transfer_masuk', 'transfer_keluar']);
            $table->decimal('jumlah', 15, 2);
            $table->text('deskripsi')->nullable();
            $table->string('bukti_file')->nullable(); // path upload gambar/pdf
            $table->foreignId('dompet_tujuan_id')->nullable()->constrained('dompet_kas'); // utk transfer
            $table->foreignId('event_id')->nullable(); // relasi ke tabel events (opsional)
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->enum('status', ['menunggu_ketua', 'menunggu_bendahara', 'disetujui', 'ditolak'])
                ->default('menunggu_ketua');
            $table->text('catatan_penolakan')->nullable();
            $table->timestamp('disetujui_ketua_at')->nullable();
            $table->foreignId('disetujui_ketua_oleh')->nullable()->constrained('users');
            $table->timestamp('disetujui_bendahara_at')->nullable();
            $table->foreignId('disetujui_bendahara_oleh')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
        Schema::dropIfExists('dompet_kas');
        Schema::dropIfExists('kategori_kas');
    }
};
