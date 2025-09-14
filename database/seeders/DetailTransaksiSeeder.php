<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DetailTransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('detail_transaksis')->insert([
            [
                'transaksi_id' => 1,
                'type' => 'Kredit',
                'value' => 1000000,
                'barang_id' => 1,
                'kuantitas' => 10,
                'bagian' => 'Pendapatan',
                'kategori_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'transaksi_id' => 1,
                'type' => 'Debit',
                'value' => 1000000,
                'barang_id' => null,
                'kuantitas' => null,
                'bagian' => 'Aset',
                'kategori_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'transaksi_id' => 2,
                'type' => 'Debit',
                'value' => 500000,
                'barang_id' => null,
                'kuantitas' => null,
                'bagian' => 'Pengeluaran',
                'kategori_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'transaksi_id' => 2,
                'type' => 'Kredit',
                'value' => 500000,
                'barang_id' => null,
                'kuantitas' => null,
                'bagian' => 'Aset',
                'kategori_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'transaksi_id' => 3,
                'type' => 'Debit',
                'value' => 500000,
                'barang_id' => 2,
                'kuantitas' => 50,
                'bagian' => 'Aset',
                'kategori_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'transaksi_id' => 3,
                'type' => 'Kredit',
                'value' => 500000,
                'barang_id' => null,
                'kuantitas' => null,
                'bagian' => 'Aset',
                'kategori_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
