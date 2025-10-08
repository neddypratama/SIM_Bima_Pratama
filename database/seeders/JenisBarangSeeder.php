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
        // Misal kategori_id untuk stok telur
        $stokTelur = Kategori::where('name', 'like', 'Stok Telur')->first()->id;
        $stokTray = Kategori::where('name', 'like', 'Stok Tray')->first()->id;
        $stokObat = Kategori::where('name', 'like', 'Stok Obat-Obatan')->first()->id;
        $stokSentrat = Kategori::where('name', 'like', 'Stok Sentrat')->first()->id;
        
        DB::table('jenis_barangs')->insert([
            [
                'name' => 'Telur Bebek',
                'deskripsi' => 'Kategori untuk barang Telur Bebek.',
                'kategori_id' => $stokTelur,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Telur Horn',
                'deskripsi' => 'Kategori untuk barang Telur Horn.',
                'kategori_id' => $stokTelur,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Telur Puyuh',
                'deskripsi' => 'Kategori untuk barang Telur Puyuh.',
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
            [
                'name' => 'Tray',
                'deskripsi' => 'Kategori untuk barang tray telur.',
                'kategori_id' => $stokTray,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Obat-Obatan',
                'deskripsi' => 'Kategori untuk barang berupa obat-obatan atau vitamin.',
                'kategori_id' => $stokObat,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stok Sentrat',
                'deskripsi' => 'Kategori untuk barang pakan atau sentrat ternak.',
                'kategori_id' => $stokSentrat,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
