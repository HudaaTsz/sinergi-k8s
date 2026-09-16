<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('nomor_anggota')->unique()->nullable(); // dipakai utk QR code
            $table->string('jabatan')->nullable();
            $table->string('divisi')->nullable();
            $table->string('no_telepon')->nullable();
            $table->string('foto_profil')->nullable();
            $table->enum('status_keanggotaan', ['aktif', 'nonaktif'])->default('aktif');
            $table->date('tanggal_bergabung')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
