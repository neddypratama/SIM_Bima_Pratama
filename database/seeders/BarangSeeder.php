<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('barangs')->insert([
            [
                'name' => 'Bebek Super',
                'jenis_id' => 1,
                'satuan_id' => 1,
                'stok' => 10,
                'hpp' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Horn Deluxe',
                'jenis_id' => 2,
                'satuan_id' => 1,
                'stok' => 10,
                'hpp' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Arab Premium',
                'jenis_id' => 3,
                'satuan_id' => 1,
                'stok' => 10,
                'hpp' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
