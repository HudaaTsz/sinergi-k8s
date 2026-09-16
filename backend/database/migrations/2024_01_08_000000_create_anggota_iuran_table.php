<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel khusus warga/anggota yang bayar iuran — TIDAK terhubung ke
        // tabel `users` (login system). Cukup nama & RT, tanpa email/password.
        Schema::create('anggota_iuran', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('rt', 10)->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });

        // pembayaran_iuran sebelumnya menunjuk ke users.id (user_id).
        // Sekarang di-refactor total supaya menunjuk ke anggota_iuran.id.
        // Tabel lama di-drop & dibuat ulang karena masih tahap development
        // (belum ada data iuran production yang perlu dipertahankan).
        Schema::dropIfExists('pembayaran_iuran');

        Schema::create('pembayaran_iuran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggota_iuran')->cascadeOnDelete();
            $table->foreignId('iuran_periode_id')->constrained('iuran_periode')->cascadeOnDelete();
            $table->enum('status', ['belum_lunas', 'lunas'])->default('belum_lunas');
            $table->date('tanggal_bayar')->nullable();
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksi');
            $table->timestamps();
            $table->unique(['anggota_id', 'iuran_periode_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_iuran');
        Schema::dropIfExists('anggota_iuran');
    }
};