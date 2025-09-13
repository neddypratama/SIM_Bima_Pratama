<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('transaksis')->insert([
            [
                'invoice' => 'INV001',
                'tanggal' => '2023-09-01',
                'name' => 'Transaksi 1',
                'user_id' => 1,
                'total' => 500000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'invoice' => 'INV002',
                'tanggal' => '2023-09-02',
                'name' => 'Transaksi 2',
                'user_id' => 1,
                'total' => 500000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'invoice' => 'INV003',
                'tanggal' => '2023-09-03',
                'name' => 'Transaksi 3',
                'user_id' => 1,
                'total' => 500000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
