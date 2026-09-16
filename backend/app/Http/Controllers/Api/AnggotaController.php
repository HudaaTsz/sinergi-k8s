<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AnggotaController extends Controller
{
    public function index(Request $request)
    {
        return User::query()
            ->when($request->status, fn ($q, $s) => $q->where('status_keanggotaan', $s))
            ->when($request->search, fn ($q, $s) => $q->where('nama', 'ilike', "%{$s}%"))
            ->with('roles')
            ->orderBy('nama')
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'nomor_anggota' => 'nullable|string|unique:users,nomor_anggota',
            'jabatan' => 'nullable|string',
            'divisi' => 'nullable|string',
            'no_telepon' => 'nullable|string',
            'role' => 'required|string|exists:roles,name',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['tanggal_bergabung'] = now();

        $user = User::create($data);
        $user->assignRole($data['role']);

        return response()->json($user->load('roles'), 201);
    }

    public function update(Request $request, User $anggota)
    {
        $data = $request->validate([
            'nama' => 'sometimes|string|max:255',
            'jabatan' => 'nullable|string',
            'divisi' => 'nullable|string',
            'no_telepon' => 'nullable|string',
            'status_keanggotaan' => 'sometimes|in:aktif,nonaktif',
        ]);

        $anggota->update($data);

        return response()->json($anggota);
    }

    /** Super Admin reset password akun Intern lain (misal user lupa password). */
    public function resetPassword(Request $request, User $anggota)
    {
        $data = $request->validate([
            'new_password' => 'required|string|min:8',
        ]);

        $anggota->update(['password' => Hash::make($data['new_password'])]);

        return response()->json(['message' => 'Password berhasil direset.']);
    }

    public function uploadFoto(Request $request, User $anggota)
    {
        $request->validate(['foto' => 'required|image|max:2048']);
        $path = $request->file('foto')->store('foto-profil', 'public');
        $anggota->update(['foto_profil' => $path]);

        return response()->json(['foto_profil' => $path]);
    }
}