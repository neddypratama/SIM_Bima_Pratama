<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeternakSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('peternaks')->insert([
            [
                'name' => 'Agus',
                'alamat' => 'Jl. Melati No. 1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bambang',
                'alamat' => 'Jl. Kenanga No. 2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Chandra',
                'alamat' => 'Jl. Cempaka No. 3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
