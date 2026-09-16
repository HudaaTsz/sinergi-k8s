<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus SEMUA check constraint di kolom status, apapun nama persisnya
        $constraints = DB::select("
            SELECT con.conname
            FROM pg_constraint con
            JOIN pg_attribute att ON att.attnum = ANY(con.conkey) AND att.attrelid = con.conrelid
            WHERE con.conrelid = 'pembayaran_iuran'::regclass
            AND con.contype = 'c'
            AND att.attname = 'status'
        ");

        foreach ($constraints as $row) {
            DB::statement('ALTER TABLE pembayaran_iuran DROP CONSTRAINT "' . $row->conname . '"');
        }

        // Pasang constraint baru yang mengizinkan 3 status
        DB::statement("
            ALTER TABLE pembayaran_iuran
            ADD CONSTRAINT pembayaran_iuran_status_check
            CHECK (status IN ('belum_lunas', 'kurang_bayar', 'lunas'))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pembayaran_iuran DROP CONSTRAINT IF EXISTS pembayaran_iuran_status_check');

        DB::statement("
            ALTER TABLE pembayaran_iuran
            ADD CONSTRAINT pembayaran_iuran_status_check
            CHECK (status IN ('belum_lunas', 'lunas'))
        ");
    }
};