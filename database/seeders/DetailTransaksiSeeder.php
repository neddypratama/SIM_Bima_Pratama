<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Kategori;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use Illuminate\Support\Str;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DetailTransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
                ['name' => 'Kas Tunai', 'total' => 45046979, 'kode' => 'TNI', 'type' => 'Debit'],
                ['name' => 'Kas Deby' , 'total' => 350000000, 'kode' => 'DBY', 'type' => 'Debit'],
                ['name' => 'Bank BCA Binti Wasilah', 'total' => 95822427, 'kode' => 'TFR', 'type' => 'Debit'],
                ['name' => 'Bank BCA Masduki'      , 'total' => 401722732, 'kode' => 'TFR', 'type' => 'Debit'],
                ['name' => 'Bank BNI Binti Wasilah', 'total' => 500540291, 'kode' => 'TFR', 'type' => 'Debit'],
                ['name' => 'Bank BNI Bima Pratama' , 'total' => 13435613, 'kode' => 'TFR', 'type' => 'Debit'],
                ['name' => 'Bank BRI Binti Wasilah', 'total' => 523716647, 'kode' => 'TFR', 'type' => 'Debit'],
                ['name' => 'Bank BRI Masduki'      , 'total' => 53739727, 'kode' => 'TFR', 'type' => 'Debit'],
                ['name' => 'Hutang Bank'      , 'total' => 500000000, 'kode' => 'UTG', 'type' => 'Kredit'],
        ];

        foreach ($data as $item) {

            $kategori = Kategori::where('name', 'like', $item['name'])->first();
            $kode = $item['kode'];
            $type = $item['type'];
            
            $userId = 1; 
            $tanggal = '2026-02-01 00:00:00';
            $date = \Carbon\Carbon::parse($tanggal)->format('Ymd');

            $prefix = "INV-$date-$kode-";
            $inv = "INV-$date-AWL-";
            $str = Str::upper(Str::random(4));
            $beda = ($type == 'Kredit') ? 'Debit' : 'Kredit';

            $kate = Kategori::where('name', 'like', 'Modal Awal')->first();

            $transaksi = Transaksi::create([
                'invoice'   => $prefix . $str,
                'name'      => 'Saldo ' . $item['name'],
                'user_id'   => $userId,
                'tanggal'   => $tanggal,
                'client_id' => null,
                'type'      => $type,
                'total'     => $item['total'],
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $transaksi->id,
                'kategori_id'  => $kategori->id,
                'kuantitas'    => null,
                'value'        => null,
                'sub_total'    => $item['total'],
            ]);

            $awal = Transaksi::create([
                'invoice'   => $inv . $str,
                'name'      => "Saldo Awal " . $item['name'],
                'user_id'   => $userId,
                'tanggal'   => $tanggal,
                'client_id' => null,
                'type'      => $beda,
                'total'     => $item['total'],
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $awal->id,
                'kategori_id'  => $kate->id,
                'kuantitas'    => null,
                'value'        => null,
                'sub_total'    => $item['total'],
            ]);
        }

    }
}
