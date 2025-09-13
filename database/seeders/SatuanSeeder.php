<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('satuans')->insert([
            [
                'name' => 'Kilogram',
                'deskripsi' => '10 butir sama dengan 1 kilogram',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Liter',
                'deskripsi' => '1 liter sama dengan 1000 mililiter',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
