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
                'kategori_id' => 1, // Assuming this ID exists in the kategoris table
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Horn Deluxe',
                'kategori_id' => 2, // Assuming this ID exists in the kategoris table
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Arab Premium',
                'kategori_id' => 3, // Assuming this ID exists in the kategoris table
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
