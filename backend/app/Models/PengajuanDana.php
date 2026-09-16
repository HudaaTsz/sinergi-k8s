<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanDana extends Model
{
    protected $table = 'pengajuan_dana';
    protected $fillable = [
        'kode', 'user_id', 'judul', 'keterangan', 'jumlah_diajukan', 'event_id',
        'lampiran_file', 'status', 'ketua_id', 'ketua_at', 'catatan_ketua',
        'bendahara_id', 'bendahara_at', 'catatan_bendahara',
        'transaksi_pencairan_id', 'dicairkan_at',
    ];
    protected $casts = [
        'jumlah_diajukan' => 'decimal:2',
        'ketua_at' => 'datetime', 'bendahara_at' => 'datetime', 'dicairkan_at' => 'datetime',
    ];

    public function pemohon() { return $this->belongsTo(User::class, 'user_id'); }
}
