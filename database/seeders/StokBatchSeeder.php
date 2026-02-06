<?php

namespace Database\Seeders;

use App\Models\Barang;
use Illuminate\Database\Seeder;
use App\Models\StokBatch;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Kategori;
use Carbon\Carbon;
use Illuminate\Support\Str;

class StokBatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataBarang = [
            ["id" => "1","name" => "Golden","stok" => "37565.00","hpp" => "2150.00",],
            ["id" => "2","name" => "BK","stok" => "2479.00","hpp" => "1650.00",],
            ["id" => "3","name" => "Asin Pth","stok" => "0.00","hpp" => "1421.00",],
            ["id" => "4","name" => "AB Pth","stok" => "0.00","hpp" => "900.00",],
            ["id" => "5","name" => "Horen","stok" => "7858.08","hpp" => "23800.00",],
            ["id" => "6","name" => "Horen Pth","stok" => "8.00","hpp" => "20000.00",],
            ["id" => "7","name" => "Puyuh Bj","stok" => "43650.00","hpp" => "270.00",],
            ["id" => "8","name" => "Puyuh Kg","stok" => "486.78","hpp" => "24000.00",],
            ["id" => "9","name" => "Asin","stok" => "425.00","hpp" => "2257.00",],
            ["id" => "10","name" => "Arab Mrh","stok" => "30371.00","hpp" => "1350.00",],
            ["id" => "11","name" => "Arab Pct","stok" => "0.00","hpp" => null,],
            ["id" => "12","name" => "Puyuh Baru DM","stok" => "333.00","hpp" => "59000.00",],
            ["id" => "13","name" => "Puyuh Second","stok" => "0.00","hpp" => "45000.00",],
            ["id" => "14","name" => "Puyuh Tutup","stok" => "20.00","hpp" => "24500.00",],
            ["id" => "15","name" => "DM","stok" => "383.00","hpp" => "63000.00",],
            ["id" => "16","name" => "Horen Baru","stok" => "228.00","hpp" => "48000.00",],
            ["id" => "17","name" => "Horen Second","stok" => "10.00","hpp" => "35000.00",],
            ["id" => "18","name" => "Horen Tutup","stok" => "11.00","hpp" => "35000.00",],
            ["id" => "19","name" => "PMS","stok" => "0.00","hpp" => null,],
            ["id" => "20","name" => "Asin","stok" => "35.27","hpp" => "46000.00",],
            ["id" => "21","name" => "Asin Tutup","stok" => "0.21","hpp" => "25000.00",],
            ["id" => "22","name" => "Bebek","stok" => "628.00","hpp" => "35000.00",],
            ["id" => "23","name" => "KK","stok" => "87.00","hpp" => "43000.00",],
            ["id" => "24","name" => "MEDAN","stok" => "2.00","hpp" => "46000.00",],
            ["id" => "25","name" => "CPL","stok" => "178.40","hpp" => "21000.00",],
            ["id" => "26","name" => "NEOBRO 250gr","stok" => "0.00","hpp" => "31000.00",],
            ["id" => "27","name" => "VITA STRESS 250gr","stok" => "16.00","hpp" => "23000.00",],
            ["id" => "29","name" => "FORTEVIT 250gr","stok" => "28.00","hpp" => "128000.00",],
            ["id" => "30","name" => "EGG STIMULANT","stok" => "36.00","hpp" => "37000.00",],
            ["id" => "32","name" => "THERAPY","stok" => "6.00","hpp" => "68500.00",],
            ["id" => "34","name" => "TURBO 250gr","stok" => "12.00","hpp" => "37400.00",],
            ["id" => "35","name" => "VITANAK","stok" => "0.00","hpp" => "18500.00",],
            ["id" => "36","name" => "KOLERIDIN 250gr","stok" => "16.00","hpp" => "59000.00",],
            ["id" => "37","name" => "BROMOQUAD","stok" => "8.00","hpp" => "92000.00",],
            ["id" => "38","name" => "ANTISEPT","stok" => "2.00","hpp" => "269500.00",],
            ["id" => "39","name" => "OBAT GUREM 15gr","stok" => "87.00","hpp" => "2733.00",],
            ["id" => "40","name" => "LEVAMIDE (1 dus isi 10)","stok" => "7.00","hpp" => "34250.00",],
            ["id" => "41","name" => "DOXERIN PLUS","stok" => "7.00","hpp" => "172000.00",],
            ["id" => "42","name" => "COLAMOX","stok" => "2.00","hpp" => "53000.00",],
            ["id" => "43","name" => "DOXERIN","stok" => "0.00","hpp" => "69000.00",],
            ["id" => "44","name" => "CAPRIMUN E","stok" => "36.00","hpp" => "28500.00",],
            ["id" => "45","name" => "AMINOVIT","stok" => "0.00","hpp" => null,],
            ["id" => "46","name" => "PARAGIN 250gr","stok" => "4.00","hpp" => "18000.00",],
            ["id" => "48","name" => "RISAKOL 500ml","stok" => "0.00","hpp" => null,],
            ["id" => "49","name" => "RISAKOL 200ml","stok" => "0.00","hpp" => null,],
            ["id" => "50","name" => "RISAKOL KECIL 90kp","stok" => "0.00","hpp" => null,],
            ["id" => "51","name" => "RISAKOL 450kp","stok" => "1.00","hpp" => "93500.00",],
            ["id" => "52","name" => "VITACHICK KECIL 250gr","stok" => "6.00","hpp" => "34000.00",],
            ["id" => "53","name" => "AVIT 200gr","stok" => "0.00","hpp" => null,],
            ["id" => "54","name" => "SUPER EGG 200gr","stok" => "0.00","hpp" => null,],
            ["id" => "55","name" => "AMOXITIN 250gr","stok" => "13.00","hpp" => "84750.00",],
            ["id" => "57","name" => "LARVAZINE","stok" => "5.00","hpp" => "36000.00",],
            ["id" => "58","name" => "MEDIMILK","stok" => "0.00","hpp" => null,],
            ["id" => "59","name" => "MEDI EGG","stok" => "0.00","hpp" => null,],
            ["id" => "60","name" => "VET STREP","stok" => "0.00","hpp" => null,],
            ["id" => "61","name" => "TITOMIK SAK 25KG","stok" => "1.00","hpp" => "250000.00",],
            ["id" => "62","name" => "ASABIO STP CAIR","stok" => "5.00","hpp" => "30000.00",],
            ["id" => "63","name" => "ASABIO STP POWDER","stok" => "0.00","hpp" => "35000.00",],
            ["id" => "64","name" => "ASABIO SAK","stok" => "2.00","hpp" => "300000.00",],
            ["id" => "65","name" => "INTERTRIM","stok" => "2.00","hpp" => "131000.00",],
            ["id" => "66","name" => "MINERAL DUS (ISI 25 BKS)","stok" => "832.00","hpp" => "4800.00",],
            ["id" => "67","name" => "MINERAL LOS","stok" => "18.00","hpp" => "81000.00",],
            ["id" => "68","name" => "GROW MINERAL","stok" => "0.00","hpp" => null,],
            ["id" => "69","name" => "CURTAMIX SAK 15 KG","stok" => "6.00","hpp" => "250000.00",],
            ["id" => "70","name" => "VITADOX MP","stok" => "4.00","hpp" => "400000.00",],
            ["id" => "71","name" => "REVOBIO SAK 25 KG","stok" => "0.00","hpp" => null,],
            ["id" => "72","name" => "PREMIK KANDANG KAMBING","stok" => "0.00","hpp" => "0.00",],
            ["id" => "73","name" => "ASABIO KANDANG KAMBING","stok" => "2.00","hpp" => "375000.00",],
            ["id" => "74","name" => "144","stok" => "26.00","hpp" => "425000.00",],
            ["id" => "75","name" => "144R","stok" => "22.00","hpp" => "445000.00",],
            ["id" => "76","name" => "124P","stok" => "454.00","hpp" => "357500.00",],
            ["id" => "77","name" => "CFR","stok" => "20.00","hpp" => "444500.00",],
            ["id" => "78","name" => "BP 104","stok" => "195.00","hpp" => "329500.00",],
            ["id" => "79","name" => "511","stok" => "13.00","hpp" => "460000.00",],
            ["id" => "80","name" => "Starter","stok" => "11.00","hpp" => "416250.00",],
            ["id" => "81","name" => "Golden","stok" => "34.00","hpp" => "414000.00",],
            ["id" => "82","name" => "SLC","stok" => "335.00","hpp" => "352500.00",],
            ["id" => "83","name" => "K36","stok" => "13.00","hpp" => "370000.00",],
            ["id" => "84","name" => "PY100","stok" => "50.00","hpp" => "342500.00",],
            ["id" => "85","name" => "KLK SPR","stok" => "26.00","hpp" => "365000.00",],
            ["id" => "86","name" => "Grower","stok" => "0.00","hpp" => "389500.00",],
            ["id" => "87","name" => "KLK S36","stok" => "0.00","hpp" => null,],
            ["id" => "88","name" => "SLC MAX","stok" => "0.00","hpp" => "377500.00",],
            ["id" => "90","name" => "PG","stok" => "10.00","hpp" => "359000.00",],
            ["id" => "91","name" => "PARDOC","stok" => "0.00","hpp" => "425000.00",],
            ["id" => "92","name" => "B401","stok" => "5.00","hpp" => "351000.00",],
            ["id" => "93","name" => "520","stok" => "0.00","hpp" => "430000.00",],
            ["id" => "94","name" => "521","stok" => "13.00","hpp" => "405000.00",],
            ["id" => "95","name" => "Crumble A","stok" => "23.00","hpp" => "310000.00",],
            ["id" => "96","name" => "Crumble P","stok" => "16.00","hpp" => "310000.00",],
            ["id" => "97","name" => "PARS","stok" => "0.00","hpp" => "402500.00",],
            ["id" => "98","name" => "Jagung OC","stok" => "14952.00","hpp" => "6200.00",],
            ["id" => "99","name" => "Katul\/Separator","stok" => "26419.00","hpp" => "5375.09",],
            ["id" => "100","name" => "Sekam Giling","stok" => "11806.00","hpp" => "850.00",],
            ["id" => "101","name" => "Karak OC","stok" => "0.00","hpp" => "4700.00",],
            ["id" => "102","name" => "Karak Giling","stok" => "0.00","hpp" => "4700.00",],
            ["id" => "104","name" => "Jagung Giling","stok" => "2636.90","hpp" => "6700.00",],
            ["id" => "105","name" => "Katul A1 Puyuh","stok" => "520.00","hpp" => "5000.00",],
            ["id" => "106","name" => "Katul A1 Ayam","stok" => "3000.00","hpp" => "4400.00",],
            ["id" => "107","name" => "Katul A2","stok" => "3205.00","hpp" => "4100.00",],
            ["id" => "108","name" => "Katul B","stok" => "795.70","hpp" => "2000.00",],
            ["id" => "109","name" => "Katul C","stok" => "50.00","hpp" => "4900.00",],
            ["id" => "110","name" => "Kebi A","stok" => "19825.00","hpp" => "5600.00",],
            ["id" => "111","name" => "Kebi B","stok" => "1520.00","hpp" => "5000.00",],
            ["id" => "112","name" => "CAT CHOIZE OREN SALMON 800gr","stok" => "0.00","hpp" => "15920.00",],
            ["id" => "113","name" => "CAT CHOIZE HIJAU TUNA 800gr","stok" => "0.00","hpp" => "15920.00",],
            ["id" => "115","name" => "CAT CHOIZE KUNING KITTEN 1KG","stok" => "10.00","hpp" => "22500.00",],
            ["id" => "116","name" => "EXCEL UNGU IKAN 500gr","stok" => "55.00","hpp" => "9918.75",],
            ["id" => "118","name" => "EXCEL HIJAU DONAT 500gr","stok" => "40.00","hpp" => "9918.75",],
            ["id" => "120","name" => "EXCEL MOM KITTEN","stok" => "37.00","hpp" => "11112.00",],
            ["id" => "121","name" => "FELIBITE DONAT","stok" => "0.00","hpp" => "10675.00",],
            ["id" => "122","name" => "FELIBITE IKAN","stok" => "0.00","hpp" => "10675.00",],
            ["id" => "125","name" => "BOLT PINK SALMON KRISTAL SAK","stok" => "0.00","hpp" => null,],
            ["id" => "126","name" => "BOLT PINK SALMON KRISTAL","stok" => "0.00","hpp" => null,],
            ["id" => "127","name" => "BOLT KUNING TUNA DONAT SAK","stok" => "25.00","hpp" => "15920.00",],
            ["id" => "128","name" => "BOLT UNGU TUNA IKAN SAK","stok" => "25.00","hpp" => "15920.00",],
            ["id" => "129","name" => "ASABIO PLUS","stok" => "2.00","hpp" => "20000.00",],
            ["id" => "130","name" => "KOLERIDIN KAPSUL","stok" => "0.00","hpp" => "17500.00",],
            ["id" => "131","name" => "INTROVIT E SELEN","stok" => "0.00","hpp" => "0.00",],
            ["id" => "132","name" => "TRIMIZIN","stok" => "31.00","hpp" => "69250.00",],
            ["id" => "133","name" => "Puyuh Baru BA","stok" => "0.00","hpp" => "60000.00",],
            ["id" => "134","name" => "524 AX","stok" => "0.00","hpp" => "347500.00",],
            ["id" => "135","name" => "DOC\/Pullet","stok" => "0.00","hpp" => "0.00",],
            ["id" => "136","name" => "K402","stok" => "0.00","hpp" => "440000.00",],
            ["id" => "137","name" => "Levamid 1 kg","stok" => "1.00","hpp" => "648000.00",],
            ["id" => "138","name" => "122","stok" => "0.00","hpp" => "389500.00",],
        ];

        // Ambil Kategori Penting
        $kateAwal  = Kategori::where('name', 'like', '%Modal Awal%')->first();
        $adminId   = 1;
        $tanggal   = now()->format('Y-m-d H:i:s');
        
        $date = \Carbon\Carbon::parse($tanggal)->format('Ymd');
        $inv = "INV-$date-AWL-";
        
        foreach ($dataBarang as $item) {
            $str = Str::upper(Str::random(4));
            if ($item['stok'] <= 0) continue;

            // 1. Buat Batch Stok
            $batch = StokBatch::create([
                'user_id'             => $adminId,
                'barang_id'           => $item['id'],
                'detail_transaksi_id' => null, // null karena migrasi awal
                'qty_masuk'           => $item['stok'],
                'qty_sisa'            => $item['stok'],
                'harga'               => $item['hpp'] ?? 0,
                'tanggal'             => $tanggal,
            ]);

            // 2. Cari Data Barang & Jenisnya untuk menentukan Kategori Transaksi
            $barang = Barang::with('jenis')->find($item['id']);
            if (!$barang) continue;

            $jenisName = $barang->jenis->name;
            $kategoriId = null;

            // Tentukan Kategori Berdasarkan Jenis
            if (Str::contains($jenisName, 'Telur')) {
                $kategoriId = Kategori::where('name', 'like', '%Stok Telur%')->first()?->id;
                $value = "INV-$date-TLR-$str";
            } elseif (Str::contains($jenisName, 'Pakan')) {
                $kategoriId = Kategori::where('name', 'like', '%Stok Pakan%')->first()?->id;
                $value = "INV-$date-STR-$str";
            } elseif (Str::contains($jenisName, 'Tray')) {
                $kategoriId = Kategori::where('name', 'like', '%Stok Tray%')->first()?->id;
                $value = "INV-$date-TRY-$str";
            } elseif (Str::contains($jenisName, 'Obat')) {
                $kategoriId = Kategori::where('name', 'like', '%Stok Obat%')->first()?->id;
                $value = "INV-$date-OBT-$str";
            }

            // 3. Buat Transaksi Masuk (Persediaan)
            $totalNilai = $item['stok'] * ($item['hpp'] ?? 0);
            
            // Transaksi untuk mencatat penambahan barang
            $transaksiBarang = Transaksi::create([
                'invoice'   => $value,
                'name'      => "Saldo Awal Barang: " . $item['name'],
                'user_id'   => $adminId,
                'tanggal'   => $tanggal,
                'client_id' => null,
                'type'      => 'Debit', // Barang masuk ke gudang
                'total'     => $totalNilai,
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $transaksiBarang->id,
                'kategori_id'  => $kategoriId,
                'barang_id'    => $item['id'],
                'kuantitas'    => $item['stok'],
                'value'        => $item['hpp'] ?? 0,
                'sub_total'    => $totalNilai,
            ]);

            // 4. Buat Transaksi Lawan (Modal/Saldo Awal) agar akuntansi balance
            // Ini mencatat dari mana asal nilai uang tersebut
            $transaksiModal = Transaksi::create([
                'invoice'   => $inv.$str,
                'name'      => "Modal Awal Persediaan: " . $item['name'],
                'user_id'   => $adminId,
                'tanggal'   => $tanggal,
                'client_id' => null,
                'type'      => 'Kredit', // Lawan dari masuk (sisi Kredit di Akuntansi)
                'total'     => $totalNilai,
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $transaksiModal->id,
                'kategori_id'  => $kateAwal->id, // Kategori Modal Awal
                'barang_id'    => $item['id'],
                'kuantitas'    => $item['stok'],
                'value'        => $item['hpp'] ?? 0,
                'sub_total'    => $totalNilai,
            ]);
        }
    }
}