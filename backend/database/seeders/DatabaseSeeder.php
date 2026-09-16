<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Super Admin',
            'Ketua',
            'Bendahara',
            'Sekretaris',
            'Koordinator Divisi',
            'Anggota',
            'Auditor',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        // Akun default per-role (dari seeder awal, password: "password")
        $usersDefault = [
            ['nama' => 'Super Administrator', 'email' => 'superadmin@sinergi.local', 'role' => 'Super Admin', 'jabatan' => 'Administrator', 'divisi' => 'Sistem'],
            ['nama' => 'Ketua', 'email' => 'ketua@sinergi.local', 'role' => 'Ketua', 'jabatan' => 'Ketua', 'divisi' => 'Pengurus'],
            ['nama' => 'Bendahara', 'email' => 'bendahara@sinergi.local', 'role' => 'Bendahara', 'jabatan' => 'Bendahara', 'divisi' => 'Keuangan'],
            ['nama' => 'Sekretaris', 'email' => 'sekretaris@sinergi.local', 'role' => 'Sekretaris', 'jabatan' => 'Sekretaris', 'divisi' => 'Administrasi'],
            ['nama' => 'Koordinator', 'email' => 'koordinator@sinergi.local', 'role' => 'Koordinator Divisi', 'jabatan' => 'Koordinator', 'divisi' => 'Kegiatan'],
            ['nama' => 'Anggota', 'email' => 'anggota@sinergi.local', 'role' => 'Anggota', 'jabatan' => 'Anggota', 'divisi' => 'Umum'],
            ['nama' => 'Auditor', 'email' => 'auditor@sinergi.local', 'role' => 'Auditor', 'jabatan' => 'Auditor', 'divisi' => 'Pengawasan'],
        ];

        $this->buatUser($usersDefault, password: 'password', prefixNomor: 'AGT');

        // Akun contoh tambahan per-role (dari seeder kedua, password: "password123")
        $usersContoh = [
            ['nama' => 'Admin', 'email' => 'admin@sinergi.org', 'role' => 'Super Admin', 'jabatan' => 'Super Admin', 'divisi' => null],
            ['nama' => 'Thomy Ketua', 'email' => 'thomy@wadas.org', 'role' => 'Ketua', 'jabatan' => 'Ketua', 'divisi' => null],
            ['nama' => 'Bhakti Ketua', 'email' => 'bhakti@wadas.org', 'role' => 'Ketua', 'jabatan' => 'Ketua', 'divisi' => null],
            ['nama' => 'Ella Bendahara', 'email' => 'ella@wadas.org', 'role' => 'Bendahara', 'jabatan' => 'Bendahara', 'divisi' => null],
            ['nama' => 'Kaila Bendahara', 'email' => 'kaila@wadas.org', 'role' => 'Bendahara', 'jabatan' => 'Bendahara', 'divisi' => null],
            ['nama' => 'Adel Sekretaris', 'email' => 'adel@wadas.org', 'role' => 'Sekretaris', 'jabatan' => 'Sekretaris', 'divisi' => null],
            ['nama' => 'Anggota', 'email' => 'anggota@wadas.org', 'role' => 'Anggota', 'jabatan' => 'Anggota', 'divisi' => null],
            ['nama' => 'Auditor', 'email' => 'auditor@wadas.org', 'role' => 'Auditor', 'jabatan' => 'Auditor', 'divisi' => null],
        ];

        $this->buatUser($usersContoh, password: 'password123', prefixNomor: 'A-');

        $this->call([
            MasterDataSeeder::class,
            RoleSeeder::class,
        ]);
    }

    /**
     * Buat/update sekumpulan user dari daftar data, dengan nomor anggota
     * berurut per-kelompok (supaya prefix AGT dan A- masing-masing mulai
     * dari 0001 tanpa bentrok satu sama lain).
     */
    protected function buatUser(array $daftar, string $password, string $prefixNomor): void
    {
        foreach ($daftar as $index => $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'nama' => $data['nama'],
                    'password' => Hash::make($password),
                    'nomor_anggota' => $prefixNomor . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'jabatan' => $data['jabatan'],
                    'divisi' => $data['divisi'] ?? null,
                    'status_keanggotaan' => 'aktif',
                    'tanggal_bergabung' => now(),
                ]
            );

            $user->syncRoles([$data['role']]);
        }
    }
}