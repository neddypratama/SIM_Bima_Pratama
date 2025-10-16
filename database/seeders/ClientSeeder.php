<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    /**
     * Jalankan seeder.
     */
    public function run(): void
    {
        // 🟢 Data dengan keterangan ELF
        $clientsElf = [
            'Agus','Bonari','Iwan','Nunuk','Candra','Eko Bedali','Kusnar','Jainal Candirejo','Cahyo','Unsu',
            'Kusuma','Sutik Candirejo','Maria','Suryanto','Sigit','Musid','Sugeng Krebet','Diki','Sugeng',
            'Suprat','Mamik','Isnafuah','Edi dawung','Rohmad','Sukar','Imam Karangbendo','Agus dawung','Wardoyo',
            'Wiji','Kaseri','Samsul dayu','Sunarman',' Elf','Gunawan','Purbo','Yanto dawung','Basuki',
            'Ika Karangbendo','Ngari','Naning','Andri Karangbendo','Rowi','B Sun','Andri Kalicilik','Ririd',
        ];

        // 🟡 Data dengan keterangan KUNING
        $clientsKuning = [
            'Suci Kuning','Topah','Joko Bonkakah','Suryono','Triono','Febri','Fadil','Kholik','Jam','Wulan','Leginah',
            'Kholis','Septi','Arif','Yadi','Nurkholis','Karyo','Safii','Arifin','Irul','Tini','Didit','Daroini',
            'Huda','Joko Manding','Kari','Saiful','Lim','Ghasa','Adi','Utami','Harmi','Tukiran','Suratman',
            'Dwin','Koim','Kafid','Hanik',
        ];

        // 🔴 Data dengan keterangan MERAH
        $clientsMerah = [
            'Pinka','Farida','Ngalimin','Karyani','Rudi bontoro','Ahmad','Wiroyo','Kasianto','Yanto Sukoanyar','Jumari',
            'Rob','Sutik Sidomulyo','Fais','Nano','Sareh','Andi','Aris','Imam sidomulyo','Korib','Irul sidomulyo',
            'Yuda puyuh','Luis','Sunarmi','Kolik sumbernanas','Edi selorejo','Erna sumberasri','Samirin','Yanto Sumberasri',
            'Rotul','Faruq','Sukiro','Sunar','Jainal Karetan','Yaul','Dewi','Savio','Likah','Rif','Kusnun','Soniah',
            'Slamet','P Sun','Nasik','Jainudin','Mufas',
        ];

        // 🏠 Data dengan keterangan RUMAH
        $clientsRumah = [
            'Sun','Toko','Sutris faksin','Edi Subkhan','Pri sumberasri','Gatot','Yusuf','Wahyu','Mariono','Winarsih',
            'Andi Jagoan','Triono kaligedok','Mad','Anti','Kontiyah','Sus','Gawing','Samsul','Sokib','Bagio','Sutinah',
            'Nanang Kalicilik','In','Mastur','Agung','Nur','Iqsan','Rindang','Nikmah','Ana','Nanang Rejoso','Siti',
            'Pom kulon','Rurin','Erna Kalicilik','Kabib','Pom candi',
        ];

        // 🐓 Data dengan keterangan KANDANG
        $clientsKandang = [
            'Kandang Puyuh Ngiwak','Kandang Puyuh Sidomulyo','Kandang bebek','Kandang ayam','Kandang kambing',
        ];

        // PEDAGANGAN
        $clientsPedagangan = [
            'Nunur',
            'Ali',
            'Prayit',
            'Suci',
            'Seneng',
        ];

        // KARYAWAN KELILING
        $clientsKaryawanKeliling = [
            'Syah', 'Agung 2', 'Tri', 'Heji', 'Arya', 'Hebi', 'Zikin', 'Ipul', 'Imam', 'Shodiq',
            'Rurin 2', 'Muda', 'Zubet', 'Iwan 2', 'Anton', 'Eko', 'Rafi', 'Son', 'Mursyid', 'Rum',
            'Sopiyah', 'Irul 2', 'Yuyun', 'Zamron cs', 'Agung 3', 'Sokib 2', 'Din',
        ];

        // KARYAWAN LAIN
        $clientsKaryawanLain = [
            'Koim 2', 'Anak Yatim', 'Didit 2', 'Ela', 'Sipon', 'Yeni', 'Ika/Aris', 'Supri Sopir', 'Sipon 2',
            'Mun', 'Binti paket', 'Pertashop', 'Polet', 'Toko 2', 'Azka', 'Zulfa', 'Kasan', 'Joko', 'Yoga', 
            'Bagas', 'Jun', 'Eka', 'Nasik 2', 'Niam', 'Mufas 2', 'Suyoto', 'Azka titip', 'Kateni', 'Ridwan',
        ];

        $clientSupplier = [
            'Tray Diamond / DM', 'Tray Super Buah / SB', 'Obat-Obatan', 'Sentrat SK', 'Sentrat Ponggok', 'Bp. Supriadi',
        ];

        $data = [];

        // ELF
        foreach ($clientsElf as $name) {
            $data[] = [
                'name' => $name,
                'alamat' => 'Jl. Melati No. ' . rand(1, 50),
                'type' => 'Peternak',
                'keterangan' => 'Elf',
                'bon' => 0,
                'titipan' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // KUNING
        foreach ($clientsKuning as $name) {
            $data[] = [
                'name' => $name,
                'alamat' => 'Jl. Kenanga No. ' . rand(1, 50),
                'type' => 'Peternak',
                'keterangan' => 'Kuning',
                'bon' => 0,
                'titipan' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // MERAH
        foreach ($clientsMerah as $name) {
            $data[] = [
                'name' => $name,
                'alamat' => 'Jl. Mawar No. ' . rand(1, 50),
                'type' => 'Peternak',
                'keterangan' => 'Merah',
                'bon' => 0,
                'titipan' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // RUMAH
        foreach ($clientsRumah as $name) {
            $data[] = [
                'name' => $name,
                'alamat' => 'Jl. Dahlia No. ' . rand(1, 50),
                'type' => 'Peternak',
                'keterangan' => 'Rumah',
                'bon' => 0,
                'titipan' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // KANDANG
        foreach ($clientsKandang as $name) {
            $data[] = [
                'name' => $name,
                'alamat' => 'Jl. Flamboyan No. ' . rand(1, 50),
                'type' => 'Peternak',
                'keterangan' => 'Kandang',
                'bon' => 0,
                'titipan' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($clientsPedagangan as $name) {
            $data[] = [
                'name' => $name,
                'alamat' => 'Jl. Anggrek No. ' . rand(1, 50),
                'type' => 'Pedagang',
                'keterangan' => null,
                'bon' => 0,
                'titipan' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($clientsKaryawanKeliling as $name) {
            $data[] = [
                'name' => $name,
                'alamat' => 'Jl. Teratai No. ' . rand(1, 50),
                'type' => 'Karyawan',
                'keterangan' => 'Karyawan Keliling',
                'bon' => 0,
                'titipan' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($clientsKaryawanLain as $name) {
        $data[] = [
            'name' => $name,
            'alamat' => 'Jl. Cempaka No. ' . rand(1, 50),
            'type' => 'Karyawan',
            'keterangan' => 'Karyawan Lain',
            'bon' => 0,
                'titipan' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            ];
        }

        // Supplier
        foreach ($clientSupplier as $name) {
        $data[] = [
            'name' => $name,
            'alamat' => 'Jl. Raflesia No. ' . rand(1, 50),
            'type' => 'Supplier',
            'keterangan' => 'Supplier',
            'bon' => 0,
                'titipan' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            ];
        }

        // POCOK
        $data[] = [
            'name' => 'Abi',
            'alamat' => 'Jl. Matahari No. 1',
            'type' => 'Peternak',
            'keterangan' => 'Pocok',
            'bon' => 0,
                'titipan' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // TERNAK KELUAR
        $data[] = [
            'name' => 'Jemangin',
            'alamat' => 'Jl. Sepatu No. 1',
            'type' => 'Peternak',
            'keterangan' => 'Ternak Keluar',
            'bon' => 0,
                'titipan' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];


        DB::table('clients')->insert($data);
    }
}
