<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranIuranLog extends Model
{
    protected $table = 'pembayaran_iuran_log';
    protected $fillable = ['pembayaran_iuran_id', 'nominal', 'tanggal_bayar', 'transaksi_id', 'dicatat_oleh'];
    protected $casts = ['nominal' => 'decimal:2', 'tanggal_bayar' => 'date'];

    public function pembayaran()
    {
        return $this->belongsTo(PembayaranIuran::class, 'pembayaran_iuran_id');
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }
}