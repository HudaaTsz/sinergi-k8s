<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_dana', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique(); // PJD-2026-0007
            $table->foreignId('user_id')->constrained('users'); // pemohon
            $table->string('judul');
            $table->text('keterangan');
            $table->decimal('jumlah_diajukan', 15, 2);
            $table->foreignId('event_id')->nullable();
            $table->string('lampiran_file')->nullable(); // proposal pendukung

            // Alur: diajukan -> disetujui_ketua -> disetujui_bendahara -> dicairkan
            $table->enum('status', [
                'diajukan', 'disetujui_ketua', 'ditolak_ketua',
                'disetujui_bendahara', 'ditolak_bendahara', 'dicairkan',
            ])->default('diajukan');

            $table->foreignId('ketua_id')->nullable()->constrained('users');
            $table->timestamp('ketua_at')->nullable();
            $table->text('catatan_ketua')->nullable();

            $table->foreignId('bendahara_id')->nullable()->constrained('users');
            $table->timestamp('bendahara_at')->nullable();
            $table->text('catatan_bendahara')->nullable();

            $table->foreignId('transaksi_pencairan_id')->nullable()->constrained('transaksi');
            $table->timestamp('dicairkan_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_dana');
    }
};
