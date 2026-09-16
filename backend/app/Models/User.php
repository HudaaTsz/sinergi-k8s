<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;

    protected $fillable = [
        'nama', 'email', 'password', 'nomor_anggota', 'jabatan', 'divisi',
        'no_telepon', 'foto_profil', 'status_keanggotaan', 'tanggal_bergabung',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'tanggal_bergabung' => 'date',
        ];
    }

    public function pengajuanDana()
    {
        return $this->hasMany(PengajuanDana::class);
    }

    public function isAktif(): bool
    {
        return $this->status_keanggotaan === 'aktif';
    }

    public function qrPayload(): string
    {
        return json_encode([
            'nomor_anggota' => $this->nomor_anggota,
            'nama' => $this->nama,
        ]);
    }
}