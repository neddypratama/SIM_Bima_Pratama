<?php

namespace Database\Seeders;

use App\Models\JenisBarang;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil id dari setiap jenis barang
        $telurBebek   = JenisBarang::where('name', 'Telur Bebek')->first()->id ?? null;
        $telurHorn    = JenisBarang::where('name', 'Telur Horn')->first()->id ?? null;
        $telurPuyuh   = JenisBarang::where('name', 'Telur Puyuh')->first()->id ?? null;
        $telurArab    = JenisBarang::where('name', 'Telur Arab')->first()->id ?? null;
        $tray         = JenisBarang::where('name', 'Tray')->first()->id ?? null;
        $obat         = JenisBarang::where('name', 'Obat-Obatan')->first()->id ?? null;
        $sentrat      = JenisBarang::where('name', 'Stok Sentrat')->first()->id ?? null;

        $barangs = [
            // 🥚 Telur Bebek
            ['Golden', $telurBebek, 10],
            ['BK', $telurBebek, 10],

            // 🥚 Telur Horn
            ['Horen', $telurHorn, 10],
            ['Horen Pth', $telurHorn, 10],

            // 🐥 Telur Puyuh
            ['Puyuh Bj', $telurPuyuh, 10],
            ['Puyuh Kg', $telurPuyuh, 10],
            ['Asin', $telurPuyuh, 10],

            // 🐔 Telur Arab
            ['Arab Mrh', $telurArab, 10],
            ['Arab Pct', $telurArab, 10],

            // 📦 Tray
            ['SB Baru', $tray, 10],
            ['Puyuh', $tray, 10],
            ['DM', $tray, 10],
            ['PMS', $tray, 10],
            ['KK', $tray, 10],
            ['MEDAN', $tray, 10],
            ['TRAY ASIN SUT', $tray, 10],

            // 💊 Obat-Obatan
            ['NEOBRO 250gr', $obat, 10],
            ['VITA STRESS 250gr', $obat, 10],
            ['VITA STRESS DUS', $obat, 10],
            ['FORTEVIT 250gr', $obat, 10],
            ['EGG STIMULANT', $obat, 10],
            ['EGG STIMULANT DUS', $obat, 10],
            ['THERAPY', $obat, 10],
            ['TRYMIZINE 250gr', $obat, 10],
            ['TURBO 250gr', $obat, 10],
            ['VITANAK', $obat, 10],
            ['KOLERIDIN 250gr', $obat, 10],
            ['BROMOQUAD', $obat, 10],
            ['ANTISEPT', $obat, 10],
            ['OBAT GUREM 15gr', $obat, 10],
            ['LEVAMID 100gr', $obat, 10],
            ['DOXERIN PLUS', $obat, 10],
            ['COLAMOX', $obat, 10],
            ['DOXERIN', $obat, 10],
            ['CAPRIMUN E', $obat, 10],
            ['AMINOVIT', $obat, 10],
            ['PARAGIN 250gr', $obat, 10],
            ['PARAGIN 100gr', $obat, 10],
            ['RISAKOL 500ml', $obat, 10],
            ['RISAKOL 200ml', $obat, 10],
            ['RISAKOL KECIL 90kp', $obat, 10],
            ['RISAKOL 450kp', $obat, 10],
            ['VITACHICK KECIL 250gr', $obat, 10],
            ['AVIT 200gr', $obat, 10],
            ['SUPER EGG 200gr', $obat, 10],
            ['CAT CHOIZE OREN SALMON 800gr', $obat, 10],
            ['CAT CHOIZE HIJAU TUNA 800gr', $obat, 10],
            ['CAT CHOIZE HIJAU TUNA 801gr Sak', $obat, 10],
            ['CAT CHOIZE KUNING KITTEN 1KG', $obat, 10],
            ['EXCEL UNGU IKAN 500gr', $obat, 10],
            ['EXCEL UNGU IKAN 501gr Sak', $obat, 10],
            ['EXCEL HIJAU DONAT 500gr', $obat, 10],
            ['EXCEL HIJAU DONAT 500gr SAK', $obat, 10],
            ['EXCEL MOM KITTEN', $obat, 10],
            ['FELIBITE DONAT', $obat, 10],
            ['FELIBITE IKAN', $obat, 10],
            ['FELIBITE IKAN SAK', $obat, 10],
            ['FELIBITE DONAT SAK', $obat, 10],
            ['BOLT PINK SALMON KRISTAL SAK', $obat, 10],
            ['BOLT PINK SALMON KRISTAL', $obat, 10],
            ['BOLT KUNING TUNA DONAT SAK', $obat, 10],
            ['BOLT UNGU TUNA IKAN SAK', $obat, 10],
            ['AMOXITIN 250gr', $obat, 10],
            ['AMOXITIN DUS', $obat, 10],
            ['LARVASIN', $obat, 10],
            ['MEDIMILK', $obat, 10],
            ['MEDI EGG', $obat, 10],
            ['VET STREP', $obat, 10],
            ['TITOMIK SAK 25KG', $obat, 10],
            ['ASABIO STP CAIR', $obat, 10],
            ['ASABIO STP POWDER', $obat, 10],
            ['ASABIO SAK', $obat, 10],
            ['INTERTRIM', $obat, 10],
            ['MINERAL DUS', $obat, 10],
            ['MINERAL LOS', $obat, 10],
            ['GROW MINERAL', $obat, 10],
            ['CURTAMIX SAK 15 KG', $obat, 10],
            ['VITADOX MP', $obat, 10],
            ['REVOBIO SAK 25 KG', $obat, 10],
            ['PREMIK KANDANG KAMBING', $obat, 10],
            ['ASABIO KANDANG KAMBING', $obat, 10],

            // 🌾 Stok Sentrat
            ['144', $sentrat, 10],
            ['144R', $sentrat, 10],
            ['124P', $sentrat, 10],
            ['CFR', $sentrat, 10],
            ['BP', $sentrat, 10],
            ['511', $sentrat, 10],
            ['Stater', $sentrat, 10],
            ['Golden', $sentrat, 10],
            ['SLC', $sentrat, 10],
            ['K36', $sentrat, 10],
            ['PY', $sentrat, 10],
            ['NF', $sentrat, 10],
            ['Grower', $sentrat, 10],
            ['591 Kardus', $sentrat, 10],
            ['594 Kardus', $sentrat, 10],
            ['PRIMA', $sentrat, 10],
            ['PG', $sentrat, 10],
            ['PARDOK', $sentrat, 10],
            ['B401', $sentrat, 10],
            ['520', $sentrat, 10],
            ['521', $sentrat, 10],
            ['611', $sentrat, 10],
            ['612', $sentrat, 10],
            ['511 Kardus', $sentrat, 10],
        ];

        $insertData = [];
        foreach ($barangs as [$nama, $jenis, $stok]) {
            $insertData[] = [
                'name' => $nama,
                'jenis_id' => $jenis,
                'stok' => $stok,
                'hpp' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('barangs')->insert($insertData);
    }
}
