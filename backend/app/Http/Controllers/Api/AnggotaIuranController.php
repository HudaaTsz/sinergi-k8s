<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnggotaIuran;
use Illuminate\Http\Request;

class AnggotaIuranController extends Controller
{
    public function index(Request $request)
    {
        return AnggotaIuran::query()
            ->when($request->rt, fn ($q, $rt) => $q->where('rt', $rt))
            ->where('status', 'aktif')
            ->orderBy('rt')->orderBy('nama')
            ->get();
    }

    /** Tambah anggota iuran baru — cukup Nama & RT, tidak butuh akun login. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'rt' => 'required|string|max:10',
        ]);

        $anggota = AnggotaIuran::create($data);

        return response()->json($anggota, 201);
    }

    public function update(Request $request, AnggotaIuran $anggotaIuran)
    {
        $data = $request->validate([
            'nama' => 'sometimes|string|max:255',
            'rt' => 'sometimes|string|max:10',
            'status' => 'sometimes|in:aktif,nonaktif',
        ]);

        $anggotaIuran->update($data);

        return response()->json($anggotaIuran);
    }

    public function destroy(AnggotaIuran $anggotaIuran)
    {
        $anggotaIuran->update(['status' => 'nonaktif']);
        return response()->json(['message' => 'Anggota dinonaktifkan.']);
    }
}