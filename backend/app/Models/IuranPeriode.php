<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IuranPeriode extends Model
{
    protected $table = 'iuran_periode';
    protected $fillable = ['nama', 'besaran', 'jatuh_tempo'];
    protected $casts = ['jatuh_tempo' => 'date', 'besaran' => 'decimal:2'];

    public function pembayaran() { return $this->hasMany(PembayaranIuran::class); }
}
