<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated Logic seeder ini sudah digabung ke DatabaseSeeder untuk
 * menghindari duplikasi. File ini dipertahankan sebagai alias supaya
 * pemanggilan lama (php artisan db:seed --class=RoleSeeder) tetap berfungsi.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);
    }
}