<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DokumenChunk extends Model
{
    protected $table = 'dokumen_chunks';
    protected $fillable = ['dokumen_id', 'isi_teks', 'embedding'];
    // 'embedding' disimpan/di-query manual via DB::statement karena tipe vector
    // bukan tipe native Eloquent (lihat AIService::simpanChunkDenganEmbedding()).
}
