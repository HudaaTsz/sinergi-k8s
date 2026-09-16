<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // "Seminar Nasional 2026"
            $table->text('deskripsi')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->decimal('budget', 15, 2)->default(0);
            $table->foreignId('koordinator_id')->nullable()->constrained('users');
            $table->enum('status', ['perencanaan', 'berjalan', 'selesai'])->default('perencanaan');
            $table->timestamps();
        });

        Schema::create('event_sponsor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('nama_sponsor');
            $table->decimal('jumlah', 15, 2);
            $table->enum('tipe', ['sponsor', 'donatur']);
            $table->timestamps();
        });

        Schema::create('event_dokumentasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('caption')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_dokumentasi');
        Schema::dropIfExists('event_sponsor');
        Schema::dropIfExists('events');
    }
};
