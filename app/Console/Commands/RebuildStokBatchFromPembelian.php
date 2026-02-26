<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Transaksi;
use App\Models\StokBatch;

class RebuildStokBatchFromPembelian extends Command
{
    // Signature ditambahkan opsi truncate agar lebih fleksibel
    protected $signature = 'stok:rebuild-batch-pembelian {--truncate : Kosongkan stok_batches sebelum proses}';

    protected $description = 'Membangun ulang stok batch dari transaksi tipe Debit dengan kategori Stok';

    public function handle()
    {
        $this->info('🚀 Memulai rebuild stok batch...');

        if ($this->option('truncate')) {
            $this->warn('⚠ Mengosongkan tabel stok_batches...');
            StokBatch::truncate();
        }

        // 1. Definisikan Query Utama
        // Kita hanya mengambil Transaksi bertipe Debit yang memiliki detail dengan kategori 'Stok%'
        $query = Transaksi::where('type', 'Debit')
            ->whereHas('details.kategori', function ($q) {
                $q->where('name', 'like', 'Stok%');
            })
            ->with(['details' => function ($q) {
                // Eager load hanya detail yang kategorinya Stok agar tidak mubazir
                $q->whereHas('kategori', function ($sq) {
                    $sq->where('name', 'like', 'Stok%');
                })->with('barang:id,name');
            }])
            ->orderBy('tanggal', 'asc')
            ->orderBy('id', 'asc');

        $totalBatch = 0;

        DB::beginTransaction();

        try {
            // 2. Gunakan chunkById untuk menangani data besar tanpa memakan RAM berlebih
            $query->chunkById(100, function ($transactions) use (&$totalBatch) {
                foreach ($transactions as $trx) {
                    foreach ($trx->details as $detail) {
                        
                        // Validasi keberadaan barang dan qty
                        if (!$detail->barang || ($detail->qty ?? 0) <= 0) {
                            continue;
                        }

                        // Logika penentuan harga (Fallback ke sub_total / qty jika harga kosong)
                        $harga = $detail->harga > 0 
                            ? $detail->harga 
                            : ($detail->qty > 0 ? $detail->sub_total / $detail->qty : 0);

                        StokBatch::create([
                            'barang_id'           => $detail->barang_id,
                            'user_id'             => 1, // Sesuaikan dengan logika sistem Anda
                            'tanggal'             => $trx->tanggal,
                            'qty_masuk'           => $detail->qty,
                            'qty_sisa'            => $detail->qty, // Karena rebuild awal, sisa = masuk
                            'harga'               => $harga,
                            'detail_transaksi_id' => $detail->id,
                        ]);

                        $totalBatch++;
                    }
                }
                $this->comment("Sedang memproses... Total sementara: {$totalBatch}");
            });

            DB::commit();
            $this->info("✅ Berhasil! {$totalBatch} data batch telah dibuat.");
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('❌ Terjadi kesalahan saat rebuild:');
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}