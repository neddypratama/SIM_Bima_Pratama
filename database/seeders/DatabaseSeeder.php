<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        User::factory(25)->create();
        $this->call(UserSeeder::class);
        $this->call(KategoriSeeder::class);
        $this->call(SatuanSeeder::class);
        $this->call(JenisBarangSeeder::class);
        $this->call(BarangSeeder::class);
        $this->call(ClientSeeder::class);
        // $this->call(TransaksiSeeder::class);
        // $this->call(DetailTransaksiSeeder::class);
    }
}
