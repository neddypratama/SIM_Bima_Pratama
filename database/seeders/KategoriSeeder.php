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
            [
                'name' => 'Penjualan telur Bebek',
                'deskripsi' => 'Kategori untuk dari penjualan bebek.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan telur Horn',
                'deskripsi' => 'Kategori untuk dari penjualan horn.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan telur Arab',
                'deskripsi' => 'Kategori untuk dari penjualan arab.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Penjualan telur Puyuh',
                'deskripsi' => 'Kategori untuk dari penjualan puyuh.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pendapatan Penjualan Sentrat',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan sentrat.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pendapatan Penjualan Obat',
                'deskripsi' => 'Kategori untuk pendapatan dari penjualan obat.',
                'type' => 'Pendapatan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pendapatan Lain-Lain',
                'deskripsi' => 'Kategori untuk pendapatan lainnya.',
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
                'name' => 'Pembelian Obat-Obatan',
                'deskripsi' => 'Kategori untuk pembelian obat-obatan.',
                'type' => 'Pengeluaran',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pembelian Tray',
                'deskripsi' => 'Kategori untuk pembelian tray.',
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
                'name' => 'Stok Telur',
                'deskripsi' => 'Kategori untuk stok telur.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Stok Sentrat',
                'deskripsi' => 'Kategori untuk stok sentrat.',
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
                'name' => 'Kas Tunai',
                'deskripsi' => 'Kategori untuk kas tunai.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'BCA',
                'deskripsi' => 'Kategori untuk saldo rekening BCA.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'BRI',
                'deskripsi' => 'Kategori untuk saldo rekening BRI.',
                'type' => 'Aset',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'BNI',
                'deskripsi' => 'Kategori untuk saldo rekening BNI.',
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
                'name' => 'Saldo Bp.Supriyadi',
                'deskripsi' => 'Kategori untuk saldo milik Bp. Supriyadi.',
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
                'name' => 'Hutang Obat-Obatan',
                'deskripsi' => 'Kategori untuk hutang obat-obatan.',
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
        ]);
    }
}
