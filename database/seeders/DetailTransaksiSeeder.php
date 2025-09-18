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
                'value' => 1000000,
                'barang_id' => 1,
                'kuantitas' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'transaksi_id' => 2,
                'value' => 500000,
                'barang_id' => null,
                'kuantitas' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'transaksi_id' => 3,
                'value' => 500000,
                'barang_id' => 2,
                'kuantitas' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
