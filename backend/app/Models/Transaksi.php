<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $table = 'transaksi';
    protected $fillable = [
        'kode', 'dompet_kas_id', 'kategori_id', 'jenis', 'jumlah', 'deskripsi',
        'bukti_file', 'dompet_tujuan_id', 'event_id', 'dibuat_oleh', 'status',
        'catatan_penolakan', 'disetujui_ketua_at', 'disetujui_ketua_oleh',
        'disetujui_bendahara_at', 'disetujui_bendahara_oleh',
    ];
    protected $casts = ['jumlah' => 'decimal:2'];

    public function dompet() { return $this->belongsTo(DompetKas::class, 'dompet_kas_id'); }
    public function kategori() { return $this->belongsTo(KategoriKas::class, 'kategori_id'); }
    public function pembuat() { return $this->belongsTo(User::class, 'dibuat_oleh'); }

    /** Threshold approval berlapis: > Rp500.000 wajib lewat Ketua & Bendahara */
    public const THRESHOLD_APPROVAL = 500000;

    public function butuhApprovalBerlapis(): bool
    {
        return $this->jenis === 'pengeluaran' && $this->jumlah > self::THRESHOLD_APPROVAL;
    }
}
