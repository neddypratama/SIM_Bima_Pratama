<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\StokBatch;

class RebuildStokBatch extends Command
{
    protected $signature = 'stok:rebuild-batch';
    protected $description = 'Hapus dan bangun ulang stok batch berdasarkan transaksi Stok (Debit/Kredit)';

    public function handle(): int
    {
        DB::transaction(function () {

            // ==========================
            // 1️⃣ BERSIHKAN STOK BATCH
            // ==========================
            $this->info('Menghapus semua stok batch...');
            StokBatch::query()->delete(); // JANGAN truncate

            // ==========================
            // 2️⃣ AMBIL TRANSAKSI STOK
            // ==========================
            $this->info('Mengambil transaksi stok...');

            $details = DB::table('detail_transaksis as d')
                ->join('transaksis as t', 't.id', '=', 'd.transaksi_id')
                ->join('kategoris as k', 'k.id', '=', 'd.kategori_id')
                ->join('detail_kategoris as dk', 'dk.id', '=', 'k.detail_kategori_id')
                ->join('barangs as b', 'b.id', '=', 'd.barang_id')
                ->where('k.name', 'like', 'Stok%')
                ->where('dk.type', 'Aset')
                ->orderBy('t.tanggal')
                ->orderBy('d.id')
                ->select(
                    'd.barang_id',
                    'b.name',
                    'd.kuantitas',
                    'd.value',
                    't.type',
                    't.tanggal'
                )
                ->get();

            // ==========================
            // 3️⃣ FASE 1 — DEBIT (STOK MASUK)
            // ==========================
            $this->info('Memproses transaksi Debit (stok masuk)...');

            foreach ($details->where('type', 'Debit') as $row) {
                $stok = StokBatch::create([
                    'barang_id' => $row->barang_id,
                    'user_id'   => 1,
                    'qty_masuk' => (float) $row->kuantitas,
                    'qty_sisa'  => (float) $row->kuantitas,
                    'harga'     => (float) $row->value,
                    'tanggal'   => $row->tanggal,
                ]);

                $this->warn("Stok Batch {$stok->id} | barang {$row->name} | masuk {$row->kuantitas}");
            }

            // ==========================
            // 4️⃣ FASE 2 — KREDIT (FIFO KELUAR)
            // ==========================
            $this->info('Memproses transaksi Kredit (stok keluar FIFO)...');

            foreach ($details->where('type', 'Kredit') as $row) {

                $qtyKeluar = (float) $row->kuantitas;

                $batches = StokBatch::where('barang_id', $row->barang_id)
                    ->where('qty_sisa', '>', 0)
                    ->orderBy('tanggal')
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if ($qtyKeluar <= 0) {
                        break;
                    }

                    $ambil = min($batch->qty_sisa, $qtyKeluar);

                    $batch->decrement('qty_sisa', $ambil);

                    $qtyKeluar -= $ambil;
                    }
                    $this->warn(
                        "Stok Batch update | barang {$row->name} | keluar {$row->kuantitas}"
                    );

                // ==========================
                // ⚠️ WARNING JIKA STOK KURANG
                // ==========================
                if ($qtyKeluar > 0) {
                    $this->warn(
                        "Stok kurang | barang_id {$row->barang_id} | sisa {$qtyKeluar}"
                    );
                }
            }
        });

        $this->info('✅ Rebuild stok batch selesai');
        return self::SUCCESS;
    }
}
