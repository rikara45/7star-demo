<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo publik: satu seeder yang mengisi semua data contoh (peran, portofolio,
     * penilaian 360, hasil 7 bintang). Dijalankan otomatis oleh docker/entrypoint.sh.
     */
    public function run(): void
    {
        $this->call([
            DemoSeeder::class,
        ]);
    }
}
