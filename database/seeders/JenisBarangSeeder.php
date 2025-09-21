<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JenisBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stokTelur = Kategori::where('name', 'Stok Telur')->first()->id;
        DB::table('jenis_barangs')->insert([
            [
                'name' => 'Telur Bebek',
                'deskripsi' => 'Kategori untuk barang Telur bebek.',
                'kategori_id' => $stokTelur,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Telur Horn',
                'deskripsi' => 'Kategori untuk barang Telur horn.',
                'kategori_id' => $stokTelur,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Telur Arab',
                'deskripsi' => 'Kategori untuk barang Telur Arab.',
                'kategori_id' => $stokTelur,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
