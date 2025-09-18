<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JenisBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('jenis_barangs')->insert([
            [
                'name' => 'Telur Bebek',
                'deskripsi' => 'Kategori untuk barang Telur bebek.',
                'kategori_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Telur Horn',
                'deskripsi' => 'Kategori untuk barang Telur horn.',
                'kategori_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Telur Arab',
                'deskripsi' => 'Kategori untuk barang Telur Arab.',
                'kategori_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
