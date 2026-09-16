<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Rename user_id -> anggota_id (skip kalau sudah anggota_id)
        if (Schema::hasColumn('pembayaran_iuran', 'user_id') && !Schema::hasColumn('pembayaran_iuran', 'anggota_id')) {
            $this->dropConstraintIfExists('pembayaran_iuran', 'user_id', 'f');
            $this->dropUniqueIfExists('pembayaran_iuran');

            Schema::table('pembayaran_iuran', function (Blueprint $table) {
                $table->renameColumn('user_id', 'anggota_id');
            });
        }

        // 2. Pasang foreign key anggota_id kalau belum ada
        if (!$this->constraintExists('pembayaran_iuran', 'anggota_id', 'f')) {
            Schema::table('pembayaran_iuran', function (Blueprint $table) {
                $table->foreign('anggota_id')->references('id')->on('anggota_iuran')->cascadeOnDelete();
            });
        }

        // 3. Pasang unique constraint kalau belum ada
        if (!$this->constraintExists('pembayaran_iuran', 'anggota_id', 'u')) {
            Schema::table('pembayaran_iuran', function (Blueprint $table) {
                $table->unique(['anggota_id', 'iuran_periode_id']);
            });
        }

        // 4. Tambah kolom tagihan, cicilan, kredit (kalau belum ada)
        Schema::table('pembayaran_iuran', function (Blueprint $table) {
            if (!Schema::hasColumn('pembayaran_iuran', 'tagihan')) {
                $table->decimal('tagihan', 15, 2)->default(0)->after('iuran_periode_id');
            }
            if (!Schema::hasColumn('pembayaran_iuran', 'total_dibayar')) {
                $table->decimal('total_dibayar', 15, 2)->default(0)->after('tagihan');
            }
            if (!Schema::hasColumn('pembayaran_iuran', 'kredit_terpakai')) {
                $table->decimal('kredit_terpakai', 15, 2)->default(0)->after('total_dibayar');
            }
        });

        // 5. Ubah status jadi VARCHAR biar bisa nampung 'kurang_bayar' juga
        DB::statement("ALTER TABLE pembayaran_iuran ALTER COLUMN status DROP DEFAULT");
        DB::statement("ALTER TABLE pembayaran_iuran ALTER COLUMN status TYPE VARCHAR(20)");
        DB::statement("ALTER TABLE pembayaran_iuran ALTER COLUMN status SET DEFAULT 'belum_lunas'");

        // 6. Tambah saldo_kredit di anggota_iuran (kalau belum ada)
        if (!Schema::hasColumn('anggota_iuran', 'saldo_kredit')) {
            Schema::table('anggota_iuran', function (Blueprint $table) {
                $table->decimal('saldo_kredit', 15, 2)->default(0)->after('status');
            });
        }

        // 7. Log setiap pembayaran (kalau belum ada)
        if (!Schema::hasTable('pembayaran_iuran_log')) {
            Schema::create('pembayaran_iuran_log', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pembayaran_iuran_id')->constrained('pembayaran_iuran')->cascadeOnDelete();
                $table->decimal('nominal', 15, 2);
                $table->date('tanggal_bayar');
                $table->foreignId('transaksi_id')->nullable()->constrained('transaksi')->nullOnDelete();
                $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_iuran_log');

        if (Schema::hasColumn('anggota_iuran', 'saldo_kredit')) {
            Schema::table('anggota_iuran', function (Blueprint $table) {
                $table->dropColumn('saldo_kredit');
            });
        }

        Schema::table('pembayaran_iuran', function (Blueprint $table) {
            $table->dropColumn(['tagihan', 'total_dibayar', 'kredit_terpakai']);
        });

        DB::statement("ALTER TABLE pembayaran_iuran ALTER COLUMN status DROP DEFAULT");
        DB::statement("ALTER TABLE pembayaran_iuran ALTER COLUMN status TYPE VARCHAR(20)");
        DB::statement("ALTER TABLE pembayaran_iuran ALTER COLUMN status SET DEFAULT 'belum_lunas'");

        $this->dropConstraintIfExists('pembayaran_iuran', 'anggota_id', 'f');
        $this->dropUniqueIfExists('pembayaran_iuran');

        if (Schema::hasColumn('pembayaran_iuran', 'anggota_id')) {
            Schema::table('pembayaran_iuran', function (Blueprint $table) {
                $table->renameColumn('anggota_id', 'user_id');
            });

            Schema::table('pembayaran_iuran', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users');
                $table->unique(['user_id', 'iuran_periode_id']);
            });
        }
    }

    protected function constraintExists(string $tabel, string $kolom, string $tipe): bool
    {
        $hasil = DB::select("
            SELECT con.conname
            FROM pg_constraint con
            JOIN pg_attribute att ON att.attnum = ANY(con.conkey) AND att.attrelid = con.conrelid
            WHERE con.conrelid = ?::regclass
            AND con.contype = ?
            AND att.attname = ?
        ", [$tabel, $tipe, $kolom]);

        return count($hasil) > 0;
    }

    protected function dropConstraintIfExists(string $tabel, string $kolom, string $tipe): void
    {
        $hasil = DB::select("
            SELECT con.conname
            FROM pg_constraint con
            JOIN pg_attribute att ON att.attnum = ANY(con.conkey) AND att.attrelid = con.conrelid
            WHERE con.conrelid = ?::regclass
            AND con.contype = ?
            AND att.attname = ?
        ", [$tabel, $tipe, $kolom]);

        foreach ($hasil as $row) {
            DB::statement('ALTER TABLE ' . $tabel . ' DROP CONSTRAINT "' . $row->conname . '"');
        }
    }

    protected function dropUniqueIfExists(string $tabel): void
    {
        $hasil = DB::select("
            SELECT conname FROM pg_constraint
            WHERE conrelid = ?::regclass AND contype = 'u'
        ", [$tabel]);

        foreach ($hasil as $row) {
            DB::statement('ALTER TABLE ' . $tabel . ' DROP CONSTRAINT "' . $row->conname . '"');
        }
    }
};