<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DetailKategoriSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('detail_kategoris')->truncate();

        $data = [
            // --- PENDAPATAN ---
            ['name' => 'Penjualan Telur', 'type' => 'Pendapatan', 'deskripsi' => 'Penjualan Telur Horn, Bebek, Puyuh, Arab, Asin'],
            ['name' => 'Penjualan Pakan', 'type' => 'Pendapatan', 'deskripsi' => 'Penjualan Pakan Sentrat, Kucing, Curah'],
            ['name' => 'Penjualan Obat', 'type' => 'Pendapatan', 'deskripsi' => 'Penjualan Obat-Obatan'],
            ['name' => 'Penjualan EggTray', 'type' => 'Pendapatan', 'deskripsi' => 'Penjualan EggTray'],
            ['name' => 'Pendapatan Perlengkapan', 'type' => 'Pendapatan', 'deskripsi' => 'Penjualan Triplex, Terpal, Ban Bekas, Sak Campur, Tali'],
            ['name' => 'Pendapatan Non Penjualan', 'type' => 'Pendapatan', 'deskripsi' => 'Telur Reject, Transport Setoran, Transport Pedagang'],
            ['name' => 'Penjualan Lain-Lain', 'type' => 'Pendapatan', 'deskripsi' => 'Penjualan Lain-Lain'],
            ['name' => 'Pendapatan Truk', 'type' => 'Pendapatan', 'deskripsi' => 'Pendapatan Truk'],
            ['name' => 'Pendapatan Pengadaan', 'type' => 'Pendapatan', 'deskripsi' => 'Pendapatan Pengadaan Jasa'],

            // --- PENGELUARAN ---
            ['name' => 'Beban Transport', 'type' => 'Pengeluaran', 'deskripsi' => 'Beban Transport, Beban BBM, Biaya Servis'],
            ['name' => 'Beban Bunga & Pajak', 'type' => 'Pengeluaran', 'deskripsi' => 'Beban Bunga, Pajak Kendaraan, Pajak Pendapatan'],
            ['name' => 'Beban Operasional', 'type' => 'Pengeluaran', 'deskripsi' => 'Beban Gaji, Kantor, Konsumsi, Beban TAL'],
            ['name' => 'Beban Produksi', 'type' => 'Pengeluaran', 'deskripsi' => 'Beban Telur Bentes, Ceplok, Prok, Telur Kotor, Telur Jumbo, Tray Terpakai'],
            ['name' => 'Beban Lain-Lain', 'type' => 'Pengeluaran', 'deskripsi' => 'Beban Barang Kadaluarsa, Beban Lain-Lain'],
            ['name' => 'Beban Sedekah', 'type' => 'Pengeluaran', 'deskripsi' => 'ZIS (Zakat, Infaq, Sedekah)'],
            ['name' => 'HPP', 'type' => 'Pengeluaran', 'deskripsi' => 'Harga Pokok Penjualan'],
            ['name' => 'Pengeluaran Truk', 'type' => 'Pengeluaran', 'deskripsi' => 'Pengeluaran Truk'],
            ['name' => 'Pengeluaran Pengadaan', 'type' => 'Pengeluaran', 'deskripsi' => 'Pengeluaran Pengadaan Jasa'],

            // --- ASET ---
            ['name' => 'Piutang Pihak Lain', 'type' => 'Aset', 'deskripsi' => 'Piutang Peternak, Karyawan, Pedagang'],
            ['name' => 'Piutang Supplier', 'type' => 'Aset', 'deskripsi' => 'Piutang Supplier Bp.Supriyadi'],
            ['name' => 'Piutang Tray', 'type' => 'Aset', 'deskripsi' => 'Piutang Tray Diamond, Super Buah, Random'],
            ['name' => 'Piutang Obat', 'type' => 'Aset', 'deskripsi' => 'Piutang Obat SK, Ponggok, Random, P Atok'],
            ['name' => 'Piutang Pakan', 'type' => 'Aset', 'deskripsi' => 'Piutang Sentrat SK, Ponggok, Random, Polet SK'],
            ['name' => 'Stok', 'type' => 'Aset', 'deskripsi' => 'Stok Telur, Pakan, Obat, Tray, Return'],
            ['name' => 'Kas', 'type' => 'Aset', 'deskripsi' => 'Kas Tunai, Kas Deby'],
            ['name' => 'Bank BCA', 'type' => 'Aset', 'deskripsi' => 'Bank BCA Binti Wasilah, Masduki'],
            ['name' => 'Bank BRI', 'type' => 'Aset', 'deskripsi' => 'Bank BRI Binti Wasilah, Masduki'],
            ['name' => 'Bank BNI', 'type' => 'Aset', 'deskripsi' => 'Bank BNI Binti Wasilah, Bima Pratama'],

            // --- LIABILITAS ---
            ['name' => 'Hutang Pihak Lain', 'type' => 'Liabilitas', 'deskripsi' => 'Hutang Peternak, Karyawan, Pedagang, Bank'],
            ['name' => 'Hutang Supplier', 'type' => 'Liabilitas', 'deskripsi' => 'Saldo Bp.Supriyadi'],
            ['name' => 'Hutang Tray', 'type' => 'Liabilitas', 'deskripsi' => 'Hutang Tray Diamond, Super Buah, Random'],
            ['name' => 'Hutang Obat', 'type' => 'Liabilitas', 'deskripsi' => 'Hutang Obat SK, Ponggok, Random, P Atok'],
            ['name' => 'Hutang Pakan', 'type' => 'Liabilitas', 'deskripsi' => 'Hutang Sentrat SK, Ponggok, Random, Polet SK'],

            // --- EKUITAS ---
            ['name' => 'Modal', 'type' => 'Ekuitas', 'deskripsi' => 'Modal Awal'],
        ];

        $finalData = array_map(function($item) {
            $item['created_at'] = now();
            $item['updated_at'] = now();
            return $item;
        }, $data);

        DB::table('detail_kategoris')->insert($finalData);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}