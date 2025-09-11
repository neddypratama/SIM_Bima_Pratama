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
                'nama' => 'Penjualan Bebek',
                'deskripsi' => 'Kategori untuk barang penjualan bebek.',
            ],
            [
                'nama' => 'Penjualan Horn',
                'deskripsi' => 'Kategori untuk barang penjualan horn.',
            ],
            [
                'nama' => 'Penjualan Arab',
                'deskripsi' => 'Kategori untuk barang penjualan Arab.',
            ],
        ]);
    }
}
