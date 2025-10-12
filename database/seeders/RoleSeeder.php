<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'name' => 'Admin',
                'deskripsi' => 'Administrator with full access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Manager',
                'deskripsi' => 'Regular manager with limited access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pembelian Telur',
                'deskripsi' => 'Administrator with full access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pakan dan Obat-Obatan',
                'deskripsi' => 'Regular manager with limited access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kas Tunai',
                'deskripsi' => 'Administrator with full access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kas Transfer',
                'deskripsi' => 'Regular manager with limited access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Akuntasi dan Rekap',
                'deskripsi' => 'Regular manager with limited access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
