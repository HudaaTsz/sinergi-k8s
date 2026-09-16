<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DompetKas extends Model
{
    protected $table = 'dompet_kas';
    protected $fillable = ['nama', 'saldo', 'divisi_id'];
    protected $casts = ['saldo' => 'decimal:2'];
}
