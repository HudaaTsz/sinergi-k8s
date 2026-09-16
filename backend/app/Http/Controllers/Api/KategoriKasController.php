<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KategoriKas;
use Illuminate\Http\Request;

class KategoriKasController extends Controller
{
    /** Tambah kategori kas baru on-the-fly saat mencatat transaksi. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255|unique:kategori_kas,nama',
            'tipe' => 'required|in:pemasukan,pengeluaran,keduanya',
        ]);

        $kategori = KategoriKas::create($data);

        return response()->json($kategori, 201);
    }
}