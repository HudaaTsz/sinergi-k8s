<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Aktifkan extension pgvector (gratis, open-source) untuk pencarian semantik
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('dokumen', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->enum('tipe', ['proposal', 'lpj', 'notulen', 'sk', 'sop', 'ad_art', 'panduan', 'lainnya']);
            $table->string('file_path');
            $table->foreignId('event_id')->nullable()->constrained('events');
            $table->foreignId('diunggah_oleh')->constrained('users');
            $table->boolean('untuk_ai_knowledge_base')->default(true);
            $table->timestamps();
        });

        // Potongan teks dokumen + embedding vector -> dipakai AI utk RAG
        Schema::create('dokumen_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokumen_id')->constrained('dokumen')->cascadeOnDelete();
            $table->text('isi_teks');
            $table->timestamps();
        });
        // Kolom vector ditambahkan manual karena Laravel schema builder belum native
        // mendukung tipe pgvector. embedding: nomic-embed-text = 768 dimensi.
        DB::statement('ALTER TABLE dokumen_chunks ADD COLUMN embedding vector(768)');
        DB::statement('CREATE INDEX dokumen_chunks_embedding_idx ON dokumen_chunks
            USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');

        Schema::create('ai_chat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->text('pertanyaan');
            $table->text('jawaban');
            $table->json('tool_calls')->nullable(); // fungsi internal yg dipanggil AI (getSaldo, dll)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_logs');
        Schema::dropIfExists('dokumen_chunks');
        Schema::dropIfExists('dokumen');
    }
};
