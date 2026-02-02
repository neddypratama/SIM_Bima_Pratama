<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('kategoris')->truncate();

        $data = [
            // --- PENDAPATAN (detail_kategori_id: 1-9) ---
            ['name' => 'Penjualan Telur Horn', 'detail_kategori_id' => 1, 'deskripsi' => 'Pendapatan dari penjualan telur horn'],
            ['name' => 'Penjualan Telur Bebek', 'detail_kategori_id' => 1, 'deskripsi' => 'Pendapatan dari penjualan telur bebek'],
            ['name' => 'Penjualan Telur Puyuh', 'detail_kategori_id' => 1, 'deskripsi' => 'Pendapatan dari penjualan telur puyuh'],
            ['name' => 'Penjualan Telur Arab', 'detail_kategori_id' => 1, 'deskripsi' => 'Pendapatan dari penjualan telur arab'],
            ['name' => 'Penjualan Telur Asin', 'detail_kategori_id' => 1, 'deskripsi' => 'Pendapatan dari penjualan telur asin'],
            ['name' => 'Penjualan Pakan Sentrat/Pabrikan', 'detail_kategori_id' => 2, 'deskripsi' => 'Penjualan pakan ternak pabrikan'],
            ['name' => 'Penjualan Pakan Kucing', 'detail_kategori_id' => 2, 'deskripsi' => 'Penjualan pakan kucing'],
            ['name' => 'Penjualan Pakan Curah', 'detail_kategori_id' => 2, 'deskripsi' => 'Penjualan pakan ternak curah'],
            ['name' => 'Penjualan Obat-Obatan', 'detail_kategori_id' => 3, 'deskripsi' => 'Penjualan obat-obatan ternak'],
            ['name' => 'Penjualan EggTray', 'detail_kategori_id' => 4, 'deskripsi' => 'Penjualan wadah telur/egg tray'],
            ['name' => 'Penjualan Triplex', 'detail_kategori_id' => 5, 'deskripsi' => 'Pendapatan dari penjualan triplex'],
            ['name' => 'Penjualan Terpal', 'detail_kategori_id' => 5, 'deskripsi' => 'Pendapatan dari penjualan terpal'],
            ['name' => 'Penjualan Ban Bekas', 'detail_kategori_id' => 5, 'deskripsi' => 'Pendapatan dari penjualan ban bekas'],
            ['name' => 'Penjualan Sak Campur', 'detail_kategori_id' => 5, 'deskripsi' => 'Pendapatan dari penjualan sak campur'],
            ['name' => 'Penjualan Tali', 'detail_kategori_id' => 5, 'deskripsi' => 'Pendapatan dari penjualan tali'],
            ['name' => 'Pemasukan Telur Reject', 'detail_kategori_id' => 6, 'deskripsi' => 'Pemasukan dari hasil telur reject'],
            ['name' => 'Pemasukan Transport Setoran', 'detail_kategori_id' => 6, 'deskripsi' => 'Jasa transport setoran'],
            ['name' => 'Pemasukan Transport Pedagang', 'detail_kategori_id' => 6, 'deskripsi' => 'Jasa transport pedagang'],
            ['name' => 'Penjualan Lain-Lain', 'detail_kategori_id' => 7, 'deskripsi' => 'Pendapatan penjualan lainnya'],
            ['name' => 'Pendapatan Truk', 'detail_kategori_id' => 8, 'deskripsi' => 'Hasil operasional truk'],
            ['name' => 'Pendapatan Pengadaan Jasa', 'detail_kategori_id' => 9, 'deskripsi' => 'Hasil jasa pengadaan'],

            // --- PENGELUARAN (detail_kategori_id: 10-18) ---
            ['name' => 'Beban Transport', 'detail_kategori_id' => 10, 'deskripsi' => 'Biaya transportasi'],
            ['name' => 'Beban Bunga', 'detail_kategori_id' => 11, 'deskripsi' => 'Beban bunga pinjaman'],
            ['name' => 'Beban Gaji', 'detail_kategori_id' => 12, 'deskripsi' => 'Biaya gaji karyawan'],
            ['name' => 'Beban Kantor', 'detail_kategori_id' => 12, 'deskripsi' => 'Biaya operasional kantor'],
            ['name' => 'Beban Konsumsi', 'detail_kategori_id' => 12, 'deskripsi' => 'Biaya konsumsi harian'],
            ['name' => 'Beban Telur Bentes', 'detail_kategori_id' => 13, 'deskripsi' => 'Kerugian telur bentes'],
            ['name' => 'Beban Telur Ceplok', 'detail_kategori_id' => 13, 'deskripsi' => 'Kerugian telur ceplok'],
            ['name' => 'Beban Telur Prok', 'detail_kategori_id' => 13, 'deskripsi' => 'Kerugian telur prok'],
            ['name' => 'Beban Barang Kadaluarsa', 'detail_kategori_id' => 13, 'deskripsi' => 'Kerugian barang expired'],
            ['name' => 'Beban Lain-Lain', 'detail_kategori_id' => 14, 'deskripsi' => 'Beban umum lainnya'],
            ['name' => 'Beban Servis', 'detail_kategori_id' => 10, 'deskripsi' => 'Biaya perbaikan kendaraan'],
            ['name' => 'Beban TAL', 'detail_kategori_id' => 12, 'deskripsi' => 'Biaya Telepon, Air, Listrik'],
            ['name' => 'Beban BBM', 'detail_kategori_id' => 10, 'deskripsi' => 'Biaya bahan bakar'],
            ['name' => 'Peralatan', 'detail_kategori_id' => 12, 'deskripsi' => 'Pembelian peralatan kecil'],
            ['name' => 'Perlengkapan', 'detail_kategori_id' => 12, 'deskripsi' => 'Pembelian perlengkapan'],
            ['name' => 'ZIS', 'detail_kategori_id' => 15, 'deskripsi' => 'Zakat, Infaq, Sedekah'],
            ['name' => 'HPP', 'detail_kategori_id' => 16, 'deskripsi' => 'Harga Pokok Penjualan'],
            ['name' => 'Beban Pajak Kendaraan', 'detail_kategori_id' => 11, 'deskripsi' => 'Pajak STNK/Kendaraan'],
            ['name' => 'Beban Telur Kotor', 'detail_kategori_id' => 13, 'deskripsi' => 'Kerugian telur kotor'],
            ['name' => 'Pengeluaran Truk', 'detail_kategori_id' => 17, 'deskripsi' => 'Biaya operasional truk'],
            ['name' => 'Beban Tray Terpakai', 'detail_kategori_id' => 13, 'deskripsi' => 'Pemakaian tray dalam produksi'],
            ['name' => 'Beban Pajak Pendapatan', 'detail_kategori_id' => 11, 'deskripsi' => 'Pajak penghasilan'],
            ['name' => 'Pengeluaran Pengadaan Jasa', 'detail_kategori_id' => 18, 'deskripsi' => 'Biaya jasa pengadaan'],
            ['name' => 'Beban Telur Jumbo', 'detail_kategori_id' => 13, 'deskripsi' => 'Kerugian telur jumbo'],

            // --- ASET (detail_kategori_id: 19-25) ---
            ['name' => 'Piutang Peternak', 'detail_kategori_id' => 19, 'deskripsi' => 'Tagihan pada peternak'],
            ['name' => 'Piutang Karyawan', 'detail_kategori_id' => 19, 'deskripsi' => 'Pinjaman karyawan'],
            ['name' => 'Piutang Pedagang', 'detail_kategori_id' => 19, 'deskripsi' => 'Tagihan pada pedagang'],
            ['name' => 'Supplier Bp.Supriyadi', 'detail_kategori_id' => 20, 'deskripsi' => 'Deposit/Saldo pada supplier'],
            ['name' => 'Piutang Tray Diamond /DM', 'detail_kategori_id' => 20, 'deskripsi' => 'Piutang tray Diamond'],
            ['name' => 'Piutang Tray Super Buah /SB', 'detail_kategori_id' => 20, 'deskripsi' => 'Piutang tray Super Buah'],
            ['name' => 'Piutang Tray Random', 'detail_kategori_id' => 20, 'deskripsi' => 'Piutang tray campuran'],
            ['name' => 'Piutang Obat SK', 'detail_kategori_id' => 20, 'deskripsi' => 'Piutang obat SK'],
            ['name' => 'Piutang Obat Ponggok', 'detail_kategori_id' => 20, 'deskripsi' => 'Piutang obat Ponggok'],
            ['name' => 'Piutang Obat Random', 'detail_kategori_id' => 20, 'deskripsi' => 'Piutang obat umum'],
            ['name' => 'Piutang Sentrat SK', 'detail_kategori_id' => 20, 'deskripsi' => 'Piutang pakan SK'],
            ['name' => 'Piutang Sentrat Ponggok', 'detail_kategori_id' => 20, 'deskripsi' => 'Piutang pakan Ponggok'],
            ['name' => 'Piutang Sentrat Random', 'detail_kategori_id' => 20, 'deskripsi' => 'Piutang pakan umum'],
            ['name' => 'Stok Telur', 'detail_kategori_id' => 21, 'deskripsi' => 'Persediaan telur'],
            ['name' => 'Stok Pakan', 'detail_kategori_id' => 21, 'deskripsi' => 'Persediaan pakan'],
            ['name' => 'Stok Obat-Obatan', 'detail_kategori_id' => 21, 'deskripsi' => 'Persediaan obat'],
            ['name' => 'Stok Tray', 'detail_kategori_id' => 21, 'deskripsi' => 'Persediaan tray'],
            ['name' => 'Stok Return', 'detail_kategori_id' => 21, 'deskripsi' => 'Persediaan barang return'],
            ['name' => 'Kas Tunai', 'detail_kategori_id' => 22, 'deskripsi' => 'Saldo uang tunai'],
            ['name' => 'Bank BCA Binti Wasilah', 'detail_kategori_id' => 23, 'deskripsi' => 'Saldo BCA Binti Wasilah'],
            ['name' => 'Bank BCA Masduki', 'detail_kategori_id' => 23, 'deskripsi' => 'Saldo BCA Masduki'],
            ['name' => 'Bank BRI Binti Wasilah', 'detail_kategori_id' => 24, 'deskripsi' => 'Saldo BRI Binti Wasilah'],
            ['name' => 'Bank BRI Masduki', 'detail_kategori_id' => 24, 'deskripsi' => 'Saldo BRI Masduki'],
            ['name' => 'Bank BNI Binti Wasilah', 'detail_kategori_id' => 25, 'deskripsi' => 'Saldo BNI Binti Wasilah'],
            ['name' => 'Bank BNI Bima Pratama', 'detail_kategori_id' => 25, 'deskripsi' => 'Saldo BNI Bima Pratama'],
            ['name' => 'Kas Deby', 'detail_kategori_id' => 22, 'deskripsi' => 'Uang tunai di Deby'],

            // --- LIABILITAS (detail_kategori_id: 26-30) ---
            ['name' => 'Hutang Peternak', 'detail_kategori_id' => 26, 'deskripsi' => 'Kewajiban pada peternak'],
            ['name' => 'Hutang Karyawan', 'detail_kategori_id' => 26, 'deskripsi' => 'Kewajiban pada karyawan'],
            ['name' => 'Hutang Pedagang', 'detail_kategori_id' => 26, 'deskripsi' => 'Kewajiban pada pedagang'],
            ['name' => 'Hutang Bank', 'detail_kategori_id' => 26, 'deskripsi' => 'Pinjaman bank'],
            ['name' => 'Saldo Bp.Supriyadi', 'detail_kategori_id' => 27, 'deskripsi' => 'Hutang saldo ke Bp. Supriyadi'],
            ['name' => 'Hutang Tray Diamond /DM', 'detail_kategori_id' => 28, 'deskripsi' => 'Kewajiban tray Diamond'],
            ['name' => 'Hutang Tray Super Buah /SB', 'detail_kategori_id' => 28, 'deskripsi' => 'Kewajiban tray Super Buah'],
            ['name' => 'Hutang Tray Random', 'detail_kategori_id' => 28, 'deskripsi' => 'Kewajiban tray random'],
            ['name' => 'Hutang Obat SK', 'detail_kategori_id' => 29, 'deskripsi' => 'Hutang obat SK'],
            ['name' => 'Hutang Obat Ponggok', 'detail_kategori_id' => 29, 'deskripsi' => 'Hutang obat Ponggok'],
            ['name' => 'Hutang Obat Random', 'detail_kategori_id' => 29, 'deskripsi' => 'Hutang obat umum'],
            ['name' => 'Hutang Sentrat SK', 'detail_kategori_id' => 30, 'deskripsi' => 'Hutang pakan SK'],
            ['name' => 'Hutang Sentrat Ponggok', 'detail_kategori_id' => 30, 'deskripsi' => 'Hutang pakan Ponggok'],
            ['name' => 'Hutang Sentrat Random', 'detail_kategori_id' => 30, 'deskripsi' => 'Hutang pakan umum'],

            // --- EKUITAS (detail_kategori_id: 31) ---
            ['name' => 'Modal', 'detail_kategori_id' => 31, 'deskripsi' => 'Saldo modal awal bisnis'],
            ['name' => 'Modal Awal', 'detail_kategori_id' => 31, 'deskripsi' => 'Data awal'],
        ];

        foreach ($data as $item) {
            DB::table('kategoris')->insert([
                'name' => $item['name'],
                'detail_kategori_id' => $item['detail_kategori_id'],
                'deskripsi' => $item['deskripsi'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}