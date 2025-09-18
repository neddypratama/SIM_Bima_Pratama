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
                'name' => 'Pendapatan Telur Bebek',
                'deskripsi' => 'Kategori untuk penjualan telur bebek.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kas Tunai',
                'deskripsi' => 'Kategori untuk kas tunai.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stok Telur',
                'deskripsi' => 'Kategori untuk stok telur.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hutang Peternak',
                'deskripsi' => 'Kategori untuk hutang peternak.',
                'type' => 'Liabilitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'HPP',
                'deskripsi' => 'Kategori untuk harga pokok pembelian.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
