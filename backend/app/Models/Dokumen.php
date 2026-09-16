<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dokumen extends Model
{
    protected $table = 'dokumen';
    protected $fillable = ['judul', 'tipe', 'file_path', 'event_id', 'diunggah_oleh', 'untuk_ai_knowledge_base'];

    public function chunks() { return $this->hasMany(DokumenChunk::class, 'dokumen_id'); }
}
