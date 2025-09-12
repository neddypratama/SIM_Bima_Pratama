<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PedagangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('pedagangs')->insert([
            [
                'name' => 'Siti',
                'alamat' => 'Jl. Mangga No. 4',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rina',
                'alamat' => 'Jl. Apel No. 5',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dewi',
                'alamat' => 'Jl. Jeruk No. 6',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
