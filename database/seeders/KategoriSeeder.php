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
                'type' => 'Pendapatan',
                'jenis_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pembelian Telur Bebek',
                'deskripsi' => 'Kategori untuk pembelian telur bebek.',
                'type' => 'Pengeluaran',
                'jenis_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kas Tunai',
                'deskripsi' => 'Kategori untuk kas tunai.',
                'type' => 'Aset',
                'jenis_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stok Telur',
                'deskripsi' => 'Kategori untuk stok telur.',
                'type' => 'Aset',
                'jenis_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hutang Peternak',
                'deskripsi' => 'Kategori untuk hutang peternak.',
                'type' => 'Liabilitas',
                'jenis_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
