<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KaryawanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('karyawans')->insert([
            [
                'name' => 'Andi',
                'alamat' => 'Jl. Merdeka No. 1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Budi',
                'alamat' => 'Jl. Sudirman No. 2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Citra',
                'alamat' => 'Jl. Thamrin No. 3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
