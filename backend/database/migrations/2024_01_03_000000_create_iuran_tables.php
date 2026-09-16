<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iuran_periode', function (Blueprint $table) {
            // definisi iuran per bulan/periode, misal "Iuran Juli 2026"
            $table->id();
            $table->string('nama');
            $table->decimal('besaran', 15, 2);
            $table->date('jatuh_tempo');
            $table->timestamps();
        });

        Schema::create('pembayaran_iuran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('iuran_periode_id')->constrained('iuran_periode');
            $table->enum('status', ['belum_lunas', 'lunas'])->default('belum_lunas');
            $table->date('tanggal_bayar')->nullable();
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksi');
            $table->timestamps();
            $table->unique(['user_id', 'iuran_periode_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_iuran');
        Schema::dropIfExists('iuran_periode');
    }
};
