<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnggotaIuran extends Model
{
    protected $table = 'anggota_iuran';
    protected $fillable = ['nama', 'rt', 'status', 'saldo_kredit'];
    protected $casts = ['saldo_kredit' => 'decimal:2'];

    public function pembayaran()
    {
        return $this->hasMany(PembayaranIuran::class, 'anggota_id');
    }
}