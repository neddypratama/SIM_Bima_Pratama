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
                'name' => 'Penjualan Telur Bebek',
                'deskripsi' => 'Kategori untuk penjualan telur bebek.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pembelian Telur Bebek',
                'deskripsi' => 'Kategori untuk pembelian telur bebek.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kas Tunai',
                'deskripsi' => 'Kategori untuk kas tunai.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
