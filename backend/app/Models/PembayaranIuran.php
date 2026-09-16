<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranIuran extends Model
{
    protected $table = 'pembayaran_iuran';
    protected $fillable = [
        'anggota_id', 'iuran_periode_id', 'status',
        'tagihan', 'total_dibayar', 'kredit_terpakai', 'tanggal_bayar',
    ];
    protected $casts = [
        'tanggal_bayar' => 'date',
        'tagihan' => 'decimal:2',
        'total_dibayar' => 'decimal:2',
        'kredit_terpakai' => 'decimal:2',
    ];

    public function anggota()
    {
        return $this->belongsTo(AnggotaIuran::class, 'anggota_id');
    }

    public function periode()
    {
        return $this->belongsTo(IuranPeriode::class, 'iuran_periode_id');
    }

    public function logPembayaran()
    {
        return $this->hasMany(PembayaranIuranLog::class);
    }

    public function sisaTagihan(): float
    {
        return max((float) $this->tagihan - (float) $this->total_dibayar, 0);
    }
}