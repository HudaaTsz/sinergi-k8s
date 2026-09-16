<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DompetKas;
use App\Models\PengajuanDana;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Alur pengajuan dana:
 * Anggota ajukan -> Ketua setujui/tolak -> Bendahara setujui/tolak -> Dana dicairkan
 *
 * PENTING: approval berlapis untuk pencairan dana SUDAH terjadi di level
 * Pengajuan Dana (Ketua + Bendahara). Saat dicairkan, Transaksi yang dibuat
 * LANGSUNG berstatus 'disetujui' (skip approval berlapis Transaksi), supaya
 * tidak ada approval ganda dan saldo langsung berkurang saat pencairan.
 */
class PengajuanDanaController extends Controller
{
    public function index(Request $request)
    {
        $query = PengajuanDana::with('pemohon');

        // Anggota biasa hanya lihat pengajuan miliknya sendiri
        if (!$request->user()->hasRole(['Ketua', 'Bendahara', 'Super Admin', 'Auditor'])) {
            $query->where('user_id', $request->user()->id);
        }

        return $query->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul' => 'required|string|max:255',
            'keterangan' => 'required|string',
            'jumlah_diajukan' => 'required|numeric|min:1',
            'event_id' => 'nullable|exists:events,id',
            'lampiran_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($request->hasFile('lampiran_file')) {
            $data['lampiran_file'] = $request->file('lampiran_file')->store('lampiran-pengajuan', 'public');
        }

        $data['user_id'] = $request->user()->id;
        $data['kode'] = 'PJD-' . now()->year . '-' . Str::padLeft((string) (PengajuanDana::count() + 1), 4, '0');
        $data['status'] = 'diajukan';

        $pengajuan = PengajuanDana::create($data);

        return response()->json($pengajuan, 201);
    }

    public function keputusanKetua(Request $request, PengajuanDana $pengajuan)
    {
        abort_unless($request->user()->hasRole(['Ketua', 'Bendahara', 'Super Admin']), 403);
        abort_unless($pengajuan->status === 'diajukan', 422, 'Status pengajuan tidak valid.');

        $setuju = (bool) $request->input('setuju');

        $pengajuan->update([
            'status' => $setuju ? 'disetujui_ketua' : 'ditolak_ketua',
            'ketua_id' => $request->user()->id,
            'ketua_at' => now(),
            'catatan_ketua' => $request->input('catatan'),
        ]);

        return response()->json($pengajuan);
    }

    public function keputusanBendahara(Request $request, PengajuanDana $pengajuan)
    {
        abort_unless($request->user()->hasRole(['Bendahara', 'Super Admin']), 403);
        abort_unless($pengajuan->status === 'disetujui_ketua', 422, 'Pengajuan belum disetujui Ketua.');

        $setuju = (bool) $request->input('setuju');

        $pengajuan->update([
            'status' => $setuju ? 'disetujui_bendahara' : 'ditolak_bendahara',
            'bendahara_id' => $request->user()->id,
            'bendahara_at' => now(),
            'catatan_bendahara' => $request->input('catatan'),
        ]);

        return response()->json($pengajuan);
    }

    /**
     * Bendahara mencairkan dana -> LANGSUNG buat Transaksi pengeluaran
     * berstatus 'disetujui' (tanpa approval berlapis lagi), karena approval
     * sudah selesai di level Pengajuan Dana. Saldo dompet langsung berkurang
     * di titik ini juga.
     */
    public function cairkan(Request $request, PengajuanDana $pengajuan)
    {
        abort_unless($request->user()->hasRole(['Bendahara', 'Super Admin']), 403);
        abort_unless($pengajuan->status === 'disetujui_bendahara', 422, 'Pengajuan belum disetujui Bendahara.');

        $data = $request->validate([
            'dompet_kas_id' => 'required|exists:dompet_kas,id',
            'kategori_id' => 'required|exists:kategori_kas,id',
        ]);

        // Cek saldo cukup sebelum cairkan
        $dompet = DompetKas::find($data['dompet_kas_id']);
        if ($dompet && $pengajuan->jumlah_diajukan > $dompet->saldo) {
            return response()->json([
                'message' => 'Saldo kas "' . $dompet->nama . '" tidak mencukupi. '
                    . 'Saldo saat ini: Rp' . number_format($dompet->saldo, 0, ',', '.'),
            ], 422);
        }

        $pengajuan = DB::transaction(function () use ($data, $pengajuan, $request, $dompet) {
            $transaksi = Transaksi::create([
                'kode' => 'TRX-' . now()->year . '-' . Str::padLeft((string) (Transaksi::count() + 1), 6, '0'),
                'dompet_kas_id' => $data['dompet_kas_id'],
                'kategori_id' => $data['kategori_id'],
                'jenis' => 'pengeluaran',
                'jumlah' => $pengajuan->jumlah_diajukan,
                'deskripsi' => "Pencairan dana: {$pengajuan->judul}",
                'event_id' => $pengajuan->event_id,
                'dibuat_oleh' => $request->user()->id,
                'status' => 'disetujui',
                // Ditandai sudah "disetujui bendahara" langsung, supaya konsisten
                // dengan transaksi lain yang lewat approval berlapis biasa —
                // dashboard/laporan yang memakai tanggal ini akan tetap akurat.
                'disetujui_bendahara_at' => now(),
                'disetujui_bendahara_oleh' => $request->user()->id,
            ]);

            $dompet->decrement('saldo', $transaksi->jumlah);

            $pengajuan->update([
                'status' => 'dicairkan',
                'transaksi_pencairan_id' => $transaksi->id,
                'dicairkan_at' => now(),
            ]);

            return $pengajuan;
        });

        return response()->json($pengajuan->fresh());
    }
}