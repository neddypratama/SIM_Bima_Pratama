<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('kategoris')->insert([
            [
                'name' => 'Penjualan Bebek',
                'deskripsi' => 'Kategori untuk barang penjualan bebek.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan Horn',
                'deskripsi' => 'Kategori untuk barang penjualan horn.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan Arab',
                'deskripsi' => 'Kategori untuk barang penjualan Arab.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
