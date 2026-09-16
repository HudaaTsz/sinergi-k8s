<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DompetKas;
use App\Models\KategoriKas;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    public function kategoriKas(Request $request)
    {
        return KategoriKas::when($request->tipe, function ($q, $tipe) {
            $q->where('tipe', $tipe)->orWhere('tipe', 'keduanya');
        })->orderBy('nama')->get();
    }

    public function dompetKas()
    {
        return DompetKas::orderBy('nama')->get();
    }
}