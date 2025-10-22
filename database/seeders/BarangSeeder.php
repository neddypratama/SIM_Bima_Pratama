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
        $telurAsin    = JenisBarang::where('name', 'Telur Asin')->first()->id ?? null;
        $tray         = JenisBarang::where('name', 'Tray')->first()->id ?? null;
        $obat         = JenisBarang::where('name', 'Obat-Obatan')->first()->id ?? null;
        $sentrat      = JenisBarang::where('name', 'Pakan Sentrat/Pabrikan')->first()->id ?? null;
        $pakanCurah      = JenisBarang::where('name', 'Pakan Curah')->first()->id ?? null;
        $pakanKucing      = JenisBarang::where('name', 'Pakan Kucing')->first()->id ?? null;

        $barangs = [
            // 🥚 Telur Bebek
            ['Golden', $telurBebek, 0],
            ['BK', $telurBebek, 0],

            // 🥚 Telur Horn
            ['Horen', $telurHorn, 0],
            ['Horen Pth', $telurHorn, 0],

            // 🐥 Telur Puyuh
            ['Puyuh Bj', $telurPuyuh, 0],
            ['Puyuh Kg', $telurPuyuh, 0],

            // 🐥 Telur Asin
            ['Asin', $telurAsin, 0],

            // 🐔 Telur Arab
            ['Arab Mrh', $telurArab, 0],
            ['Arab Pct', $telurArab, 0],

            // 📦 Tray
            ['SB Baru', $tray, 0],
            ['Puyuh', $tray, 0],
            ['DM', $tray, 0],
            ['PMS', $tray, 0],
            ['KK', $tray, 0],
            ['MEDAN', $tray, 0],
            ['TRAY ASIN SUT', $tray, 0],

            // 💊 Obat-Obatan
            ['NEOBRO 250gr', $obat, 0],
            ['VITA STRESS 250gr', $obat, 0],
            ['VITA STRESS DUS', $obat, 0],
            ['FORTEVIT 250gr', $obat, 0],
            ['EGG STIMULANT', $obat, 0],
            ['EGG STIMULANT DUS', $obat, 0],
            ['THERAPY', $obat, 0],
            ['TRYMIZINE 250gr', $obat, 0],
            ['TURBO 250gr', $obat, 0],
            ['VITANAK', $obat, 0],
            ['KOLERIDIN 250gr', $obat, 0],
            ['BROMOQUAD', $obat, 0],
            ['ANTISEPT', $obat, 0],
            ['OBAT GUREM 15gr', $obat, 0],
            ['LEVAMID 00gr', $obat, 0],
            ['DOXERIN PLUS', $obat, 0],
            ['COLAMOX', $obat, 0],
            ['DOXERIN', $obat, 0],
            ['CAPRIMUN E', $obat, 0],
            ['AMINOVIT', $obat, 0],
            ['PARAGIN 250gr', $obat, 0],
            ['PARAGIN 00gr', $obat, 0],
            ['RISAKOL 500ml', $obat, 0],
            ['RISAKOL 200ml', $obat, 0],
            ['RISAKOL KECIL 90kp', $obat, 0],
            ['RISAKOL 450kp', $obat, 0],
            ['VITACHICK KECIL 250gr', $obat, 0],
            ['AVIT 200gr', $obat, 0],
            ['SUPER EGG 200gr', $obat, 0],
            ['CAT CHOIZE OREN SALMON 800gr', $obat, 0],
            ['CAT CHOIZE HIJAU TUNA 800gr', $obat, 0],
            ['CAT CHOIZE HIJAU TUNA 801gr Sak', $obat, 0],
            ['CAT CHOIZE KUNING KITTEN 1KG', $obat, 0],
            ['EXCEL UNGU IKAN 500gr', $obat, 0],
            ['EXCEL UNGU IKAN 501gr Sak', $obat, 0],
            ['EXCEL HIJAU DONAT 500gr', $obat, 0],
            ['EXCEL HIJAU DONAT 500gr SAK', $obat, 0],
            ['EXCEL MOM KITTEN', $obat, 0],
            ['FELIBITE DONAT', $obat, 0],
            ['FELIBITE IKAN', $obat, 0],
            ['FELIBITE IKAN SAK', $obat, 0],
            ['FELIBITE DONAT SAK', $obat, 0],
            ['BOLT PINK SALMON KRISTAL SAK', $obat, 0],
            ['BOLT PINK SALMON KRISTAL', $obat, 0],
            ['BOLT KUNING TUNA DONAT SAK', $obat, 0],
            ['BOLT UNGU TUNA IKAN SAK', $obat, 0],
            ['AMOXITIN 250gr', $obat, 0],
            ['AMOXITIN DUS', $obat, 0],
            ['LARVASIN', $obat, 0],
            ['MEDIMILK', $obat, 0],
            ['MEDI EGG', $obat, 0],
            ['VET STREP', $obat, 0],
            ['TITOMIK SAK 25KG', $obat, 0],
            ['ASABIO STP CAIR', $obat, 0],
            ['ASABIO STP POWDER', $obat, 0],
            ['ASABIO SAK', $obat, 0],
            ['INTERTRIM', $obat, 0],
            ['MINERAL DUS', $obat, 0],
            ['MINERAL LOS', $obat, 0],
            ['GROW MINERAL', $obat, 0],
            ['CURTAMIX SAK 15 KG', $obat, 0],
            ['VITADOX MP', $obat, 0],
            ['REVOBIO SAK 25 KG', $obat, 0],
            ['PREMIK KANDANG KAMBING', $obat, 0],
            ['ASABIO KANDANG KAMBING', $obat, 0],

            // 🌾 Stok Sentrat
            ['144', $sentrat, 0],
            ['144R', $sentrat, 0],
            ['124P', $sentrat, 0],
            ['CFR', $sentrat, 0],
            ['BP', $sentrat, 0],
            ['511', $sentrat, 0],
            ['Stater', $sentrat, 0],
            ['Golden', $sentrat, 0],
            ['SLC', $sentrat, 0],
            ['K36', $sentrat, 0],
            ['PY', $sentrat, 0],
            ['NF', $sentrat, 0],
            ['Grower', $sentrat, 0],
            ['591 Kardus', $sentrat, 0],
            ['594 Kardus', $sentrat, 0],
            ['PRIMA', $sentrat, 0],
            ['PG', $sentrat, 0],
            ['PARDOK', $sentrat, 0],
            ['B401', $sentrat, 0],
            ['520', $sentrat, 0],
            ['521', $sentrat, 0],
            ['611', $sentrat, 0],
            ['612', $sentrat, 0],
            ['511 Kardus', $sentrat, 0],

            // Pakan Curah
            ['Jagung OC', $pakanCurah, 0],
            ['Katul/Separator', $pakanCurah, 0],
            ['Sekam Giling', $pakanCurah, 0],
            ['Karak OC', $pakanCurah, 0],
            ['Karak Giling', $pakanCurah, 0],
            ['Kebi', $pakanCurah, 0],
            ['Jagung Giling', $pakanCurah, 0],
            ['Katul A1 Puyuh', $pakanCurah, 0],
            ['Katul A1 Ayam', $pakanCurah, 0],
            ['Katul A2', $pakanCurah, 0],
            ['Katul B', $pakanCurah, 0],
            ['Katul C', $pakanCurah, 0],
            ['Kebi A', $pakanCurah, 0],
            ['Kebi B', $pakanCurah, 0],

            // Pakan Kucing
            ['CAT CHOIZE IJO', $pakanKucing, 0],
            ['CAT CHOIZE OREN', $pakanKucing, 0],
            ['CAT CHOIZE KUNING', $pakanKucing, 0],
            ['CAT CHOIZE PINK KITTEN', $pakanKucing, 0],
            ['CAT CHOIZE PINK DEWASA (TUNA)', $pakanKucing, 0],
            ['EXCEL IJO (DONAT)', $pakanKucing, 0],
            ['EXCEL UNGU (IKAN)', $pakanKucing, 0],
            ['EXCEL MOM', $pakanKucing, 0],
            ['FELIBITE PINK (IKAN)', $pakanKucing, 0],
            ['FELIDITE DONAT', $pakanKucing, 0],
            ['BOLT PINK (CRISTAL)', $pakanKucing, 0],
            ['BOLT SALMON', $pakanKucing, 0],
            ['BOLT DONAT KUNING', $pakanKucing, 0],
            ['BOLT IKAN UNGU', $pakanKucing, 0],
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
