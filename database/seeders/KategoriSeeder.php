<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('kategoris')->insert([
            // ======================
            // PENDAPATAN
            // ======================

            // --- Penjualan Telur ---
            [
                'name' => 'Penjualan Telur Horn',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan telur horn.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan Telur Bebek',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan telur bebek.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan Telur Puyuh',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan telur puyuh.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan Telur Arab',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan telur arap.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan Telur Asin',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan telur asin.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // --- Penjualan Pakan ---
            [
                'name' => 'Penjualan Pakan Sentrat/Pabrikan',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan pakan sentrat atau pabrikan.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan Pakan Kucing',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan pakan kucing.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan Pakan Curah',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan pakan curah.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // --- Penjualan Obat-Obatan ---
            [
                'name' => 'Penjualan Obat-Obatan',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan obat-obatan.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // --- Penjualan Tray ---
            [
                'name' => 'Penjualan EggTray',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan egg tray.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // --- Penjualan Perlengkapan ---
            [
                'name' => 'Penjualan Triplex',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan triplex.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan Terpal',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan terpal.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan Ban Bekas',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan ban bekas.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan Sak Campur',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan sak campur.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan Tali',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan tali.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // --- Pemasukan Non Penjualan ---
            [
                'name' => 'Pemasukan Dapur',
                'deskripsi' => 'Kategori untuk pendapatan dari pemasukan dapur.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pemasukan Transport Setoran',
                'deskripsi' => 'Kategori untuk pendapatan dari transport setoran.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pemasukan Transport Pedagang',
                'deskripsi' => 'Kategori untuk pendapatan dari transport pedagang.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan Lain-Lain',
                'deskripsi' => 'Kategori untuk penjualan lainnya.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ======================
            // PENGELUARAN
            // ======================
            [
                'name' => 'Beban Transport',
                'deskripsi' => 'Kategori untuk biaya transportasi.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Beban Bunga',
                'deskripsi' => 'Kategori untuk beban bunga pinjaman.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Beban Gaji',
                'deskripsi' => 'Kategori untuk biaya gaji karyawan.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Beban Kantor',
                'deskripsi' => 'Kategori untuk biaya operasional kantor.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Beban Konsumsi',
                'deskripsi' => 'Kategori untuk biaya konsumsi.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [ 
                'name' => 'Beban Telur Bentes', 
                'deskripsi' => 'Kategori untuk kerugian dari telur pecah.', 
                'type' => 'Pengeluaran', 
                'created_at' => now(), 
                'updated_at' => now(), 
            ], 
            [ 
                'name' => 'Beban Telur Ceplok', 
                'deskripsi' => 'Kategori untuk kerugian dari telur pecah.', 
                'type' => 'Pengeluaran', 
                'created_at' => now(), 
                'updated_at' => now(), 
            ], 
            [ 
                'name' => 'Beban Telur Prok', 
                'deskripsi' => 'Kategori untuk kerugian dari telur prok.', 
                'type' => 'Pengeluaran', 
                'created_at' => now(), 
                'updated_at' => now(), 
            ], 
            [ 
                'name' => 'Beban Barang Kadaluarsa', 
                'deskripsi' => 'Kategori untuk kerugian dari barang yang melewati masa kadaluarsa.', 
                'type' => 'Pengeluaran', 
                'created_at' => now(), 
                'updated_at' => now(), 
            ],
            [
                'name' => 'Beban Lain-Lain',
                'deskripsi' => 'Kategori untuk beban lain-lain.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Beban Servis',
                'deskripsi' => 'Kategori untuk biaya servis dan perawatan.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Beban TAL',
                'deskripsi' => 'Kategori untuk beban TAL.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Beban BBM',
                'deskripsi' => 'Kategori untuk biaya bahan bakar (BBM).',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Peralatan',
                'deskripsi' => 'Kategori untuk pengeluaran pembelian peralatan.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Perlengkapan',
                'deskripsi' => 'Kategori untuk pengeluaran perlengkapan.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ZIS',
                'deskripsi' => 'Kategori untuk Zakat, Infaq, dan Sedekah.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'HPP',
                'deskripsi' => 'Kategori untuk Harga Pokok Penjualan.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Beban Pajak',
                'deskripsi' => 'Kategori untuk beban pajak.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ======================
            // ASET
            // ======================
            [
                'name' => 'Piutang Peternak',
                'deskripsi' => 'Kategori untuk piutang kepada peternak.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Piutang Karyawan',
                'deskripsi' => 'Kategori untuk piutang kepada karyawan.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Piutang Pedagang',
                'deskripsi' => 'Kategori untuk piutang kepada pedagang.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
             [
                'name' => 'Supplier Bp.Supriyadi',
                'deskripsi' => 'Kategori untuk saldo milik Bp. Supriyadi.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Piutang Tray Diamond /DM',
                'deskripsi' => 'Kategori untuk Piutang tray Diamond.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Piutang Tray Super Buah /SB',
                'deskripsi' => 'Kategori untuk Piutang tray Super Buah.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Piutang Obat SK',
                'deSKripsi' => 'Kategori untuk Piutang obat SK.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Piutang Obat Ponggok',
                'deskripsi' => 'Kategori untuk Piutang obat Ponggok.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Piutang Obat Random',
                'deskripsi' => 'Kategori untuk Piutang obat Random.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Piutang Sentrat SK',
                'deskripsi' => 'Kategori untuk Piutang sentrat SK.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Piutang Sentrat Ponggok',
                'deskripsi' => 'Kategori untuk Piutang sentrat Ponggok.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Piutang Sentrat Random',
                'deskripsi' => 'Kategori untuk Piutang sentrat Random.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stok Telur',
                'deskripsi' => 'Kategori untuk stok telur.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stok Pakan',
                'deskripsi' => 'Kategori untuk stok pakan.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stok Obat-Obatan',
                'deskripsi' => 'Kategori untuk stok obat-obatan.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stok Tray',
                'deskripsi' => 'Kategori untuk stok tray.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stok Kotor',
                'deskripsi' => 'Kategori untuk stok kotor.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stok Return',
                'deskripsi' => 'Kategori untuk stok return.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kas Tunai',
                'deskripsi' => 'Kategori untuk kas tunai.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bank BCA Binti Wasilah',
                'deskripsi' => 'Kategori untuk saldo rekening BCA Binti Wasilah.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bank BCA Masduki',
                'deskripsi' => 'Kategori untuk saldo rekening BCA Masduki.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bank BRI Binti Wasilah',
                'deskripsi' => 'Kategori untuk saldo rekening BRI Binti Wasilah.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bank BRI Masduki',
                'deskripsi' => 'Kategori untuk saldo rekening BRI Masduki.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bank BNI Binti Wasilah',
                'deskripsi' => 'Kategori untuk saldo rekening BNI Binti Wasilah.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bank BNI Bima Pratama',
                'deskripsi' => 'Kategori untuk saldo rekening BNI Bima Pratama.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ======================
            // LIABILITAS
            // ======================
            [
                'name' => 'Hutang Peternak',
                'deskripsi' => 'Kategori untuk hutang kepada peternak.',
                'type' => 'Liabilitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hutang Karyawan',
                'deskripsi' => 'Kategori untuk hutang kepada karyawan.',
                'type' => 'Liabilitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hutang Pedagang',
                'deskripsi' => 'Kategori untuk hutang kepada pedagang.',
                'type' => 'Liabilitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hutang Bank',
                'deskripsi' => 'Kategori untuk hutang bank.',
                'type' => 'Liabilitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Saldo Bp.Supriyadi',
                'deskripsi' => 'Kategori untuk saldo milik Bp. Supriyadi.',
                'type' => 'Liabilitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hutang Tray Diamond /DM',
                'deskripsi' => 'Kategori untuk hutang tray Diamond.',
                'type' => 'Liabilitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hutang Tray Super Buah /SB',
                'deskripsi' => 'Kategori untuk hutang tray Super Buah.',
                'type' => 'Liabilitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hutang Obat SK',
                'deSKripsi' => 'Kategori untuk hutang obat SK.',
                'type' => 'Liabilitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hutang Obat Ponggok',
                'deskripsi' => 'Kategori untuk hutang obat Ponggok.',
                'type' => 'Liabilitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hutang Obat Random',
                'deskripsi' => 'Kategori untuk hutang obat Random.',
                'type' => 'Liabilitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hutang Sentrat SK',
                'deskripsi' => 'Kategori untuk hutang sentrat SK.',
                'type' => 'Liabilitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hutang Sentrat Ponggok',
                'deskripsi' => 'Kategori untuk hutang sentrat Ponggok.',
                'type' => 'Liabilitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hutang Sentrat Random',
                'deskripsi' => 'Kategori untuk hutang sentrat Random.',
                'type' => 'Liabilitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Modal Awal',
                'deskripsi' => 'Kategori untuk saldo modal awal.',
                'type' => 'Ekuitas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
