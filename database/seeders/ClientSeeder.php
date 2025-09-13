<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('clients')->insert([
            [
                'name' => 'Andi',
                'alamat' => 'Jl. Merdeka No. 1',
                'type' => 'Karyawan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Budi',
                'alamat' => 'Jl. Sudirman No. 2',
                'type' => 'Karyawan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Citra',
                'alamat' => 'Jl. Thamrin No. 3',
                'type' => 'Karyawan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
             [
                'name' => 'Siti',
                'alamat' => 'Jl. Mangga No. 4',
                'type' => 'Pedagang',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rina',
                'alamat' => 'Jl. Apel No. 5',
                'type' => 'Pedagang',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dewi',
                'alamat' => 'Jl. Jeruk No. 6',
                'type' => 'Pedagang',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Agus',
                'alamat' => 'Jl. Melati No. 1',
                'type' => 'Peternak',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bambang',
                'alamat' => 'Jl. Kenanga No. 2',
                'type' => 'Peternak',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Chandra',
                'alamat' => 'Jl. Cempaka No. 3',
                'type' => 'Peternak',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
