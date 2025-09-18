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
                'invoice' => 'INV-DPT-AAAA',
                'tanggal' => '2023-09-01',
                'name' => 'Transaksi 1',
                'type' => 'Kredit',
                'user_id' => 1,
                'client_id' => 1,
                'kategori_id' => 1,
                'total' => 500000,
                'linked_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'invoice' => 'INV-TLR-BBBB',
                'tanggal' => '2023-09-02',
                'name' => 'Transaksi 2',
                'type' => 'Debit',
                'user_id' => 1,
                'client_id' => 2,
                'kategori_id' => 3,
                'total' => 500000,
                'linked_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'invoice' => 'INV-TNI-AAAA',
                'tanggal' => '2023-09-03',
                'name' => 'Transaksi 3',
                'type' => 'Debit',
                'user_id' => 1,
                'client_id' => 1,
                'kategori_id' => 2,
                'total' => 500000,
                'linked_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
