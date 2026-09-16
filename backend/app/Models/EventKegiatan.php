<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventKegiatan extends Model
{
    protected $table = 'events';
    protected $fillable = ['nama', 'deskripsi', 'tanggal_mulai', 'tanggal_selesai', 'budget', 'koordinator_id', 'status'];
    protected $casts = ['budget' => 'decimal:2', 'tanggal_mulai' => 'date', 'tanggal_selesai' => 'date'];

    public function terpakai()
    {
        return Transaksi::where('event_id', $this->id)
            ->where('jenis', 'pengeluaran')
            ->where('status', 'disetujui')
            ->sum('jumlah');
    }

    public function sisaBudget()
    {
        return $this->budget - $this->terpakai();
    }
}
