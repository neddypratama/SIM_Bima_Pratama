<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SatuanSeeder::class);
        $this->call(JenisBarangSeeder::class);
        $this->call(RoleSeeder::class);
        User::factory(25)->create();
        $this->call(UserSeeder::class);
        $this->call(BarangSeeder::class);
        $this->call(KategoriSeeder::class);
        $this->call(PeternakSeeder::class);
        $this->call(PedagangSeeder::class);
        $this->call(KaryawanSeeder::class);
    }
}
