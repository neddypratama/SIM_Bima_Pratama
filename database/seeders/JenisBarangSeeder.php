<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JenisBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Opsional: Kosongkan tabel sebelum mengisi untuk menghindari duplikasi
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('jenis_barangs')->truncate();

        $data = [
            ["id"=>"1","name"=>"Telur Bebek","deskripsi"=>"Jenis untuk barang Telur Bebek.","kategori_id"=>"59","created_at"=>"2025-11-01 11:45:24","updated_at"=>"2025-11-01 11:45:24"],
            ["id"=>"2","name"=>"Telur Horn","deskripsi"=>"Jenis untuk barang Telur Horn.","kategori_id"=>"59","created_at"=>"2025-11-01 11:45:24","updated_at"=>"2025-11-01 11:45:24"],
            ["id"=>"3","name"=>"Telur Puyuh","deskripsi"=>"Jenis untuk barang Telur Puyuh.","kategori_id"=>"59","created_at"=>"2025-11-01 11:45:24","updated_at"=>"2025-11-01 11:45:24"],
            ["id"=>"4","name"=>"Telur Arab","deskripsi"=>"Jenis untuk barang Telur Arab.","kategori_id"=>"59","created_at"=>"2025-11-01 11:45:24","updated_at"=>"2025-11-01 11:45:24"],
            ["id"=>"5","name"=>"Telur Asin","deskripsi"=>"Jenis untuk barang Telur Asin.","kategori_id"=>"59","created_at"=>"2025-11-01 11:45:24","updated_at"=>"2025-11-01 11:45:24"],
            ["id"=>"6","name"=>"Tray","deskripsi"=>"Jenis untuk barang tray telur.","kategori_id"=>"62","created_at"=>"2025-11-01 11:45:24","updated_at"=>"2025-11-01 11:45:24"],
            ["id"=>"7","name"=>"Obat-Obatan","deskripsi"=>"Jenis untuk barang berupa obat-obatan atau vitamin.","kategori_id"=>"61","created_at"=>"2025-11-01 11:45:24","updated_at"=>"2025-11-01 11:45:24"],
            ["id"=>"8","name"=>"Pakan Sentrat/Pabrikan","deskripsi"=>"Jenis untuk barang pakan atau sentrat ternak.","kategori_id"=>"60","created_at"=>"2025-11-01 11:45:24","updated_at"=>"2025-11-01 11:45:24"],
            ["id"=>"9","name"=>"Pakan Curah","deskripsi"=>"Jenis untuk barang pakan curah.","kategori_id"=>"60","created_at"=>"2025-11-01 11:45:24","updated_at"=>"2025-11-01 11:45:24"],
            ["id"=>"10","name"=>"Pakan Kucing","deskripsi"=>"Jenis untuk barang pakan Kucing.","kategori_id"=>"60","created_at"=>"2025-11-01 11:45:24","updated_at"=>"2025-11-01 11:45:24"]
        ];

        DB::table('jenis_barangs')->insert($data);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}