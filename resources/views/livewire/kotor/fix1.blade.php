<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, Toast;

    public int $perPage = 25;

    public function headers(): array
    {
        return [['key' => 'batch_id', 'label' => 'ID Batch'], ['key' => 'detail_id', 'label' => 'ID Detail Transaksi'], ['key' => 'status', 'label' => 'Status'], ['key' => 'aksi', 'label' => 'Aksi']];
    }

    public function data()
    {
        return DB::table('stok_batches as sb')->join('detail_transaksis as dt', 'dt.id', '=', 'sb.detail_transaksi_id')->join('transaksis as t', 't.id', '=', 'dt.transaksi_id')->select('sb.id as batch_id', 'dt.id as detail_id', 'sb.tanggal as batch_tanggal', 't.tanggal as trx_tanggal')->orderByDesc('t.tanggal')->paginate($this->perPage);
    }

    public function fixTanggal(int $batchId): void
    {
        DB::transaction(function () use ($batchId) {
            $trxTanggal = DB::table('stok_batches as sb')->join('detail_transaksis as dt', 'dt.id', '=', 'sb.detail_transaksi_id')->join('transaksis as t', 't.id', '=', 'dt.transaksi_id')->where('sb.id', $batchId)->value('t.tanggal');

            DB::table('stok_batches')
                ->where('id', $batchId)
                ->update([
                    'tanggal' => $trxTanggal,
                    'updated_at' => now(),
                ]);
        });

        $this->success("Batch #$batchId disinkronkan");
    }

    public function fixAll(): void
    {
        DB::transaction(function () {
            $rows = DB::table('stok_batches as sb')->join('detail_transaksis as dt', 'dt.id', '=', 'sb.detail_transaksi_id')->join('transaksis as t', 't.id', '=', 'dt.transaksi_id')->select('sb.id as batch_id', 'sb.tanggal as batch_tanggal', 't.tanggal as trx_tanggal')->get();

            $updated = 0;

            foreach ($rows as $row) {
                if ($row->batch_tanggal != $row->trx_tanggal) {
                    DB::table('stok_batches')
                        ->where('id', $row->batch_id)
                        ->update([
                            'tanggal' => $row->trx_tanggal,
                            'updated_at' => now(),
                        ]);

                    $updated++;
                }
            }

            if ($updated > 0) {
                $this->success("Semua batch berhasil sinkron 🔥 ($updated data)");
            } else {
                $this->error('Semua batch sudah sinkron');
            }
        });
    }

    // 🔥🔥🔥 TAMBAHAN FITUR HPP 🔥🔥🔥
    public function adjustHppFromPenjualan(): void
    {
        DB::transaction(function () {
            $KAT_HPP = DB::table('kategoris')->where('name', 'HPP')->value('id');

            $KAT_STOK = DB::table('kategoris')->where('name', 'Stok Telur')->value('id');

            $KAT_PENJUALAN = DB::table('kategoris')->where('name', 'like', 'Penjualan Telur Bebek%')->value('id');

            if (!$KAT_HPP || !$KAT_STOK || !$KAT_PENJUALAN) {
                $this->error('Kategori tidak ditemukan!');
                return;
            }

            $penjualans = DB::table('transaksis as t')->join('detail_transaksis as dt', 'dt.transaksi_id', '=', 't.id')->where('dt.kategori_id', $KAT_PENJUALAN)->select('t.id', 't.invoice')->distinct()->get();

            foreach ($penjualans as $penjualan) {
                $parts = explode('-', $penjualan->invoice);

                if (count($parts) < 4) {
                    continue;
                }

                $tanggal = $parts[1];
                $kode = $parts[3];

                $hpp = DB::table('transaksis')
                    ->where('invoice', 'like', "%-$tanggal-HPP-$kode")
                    ->first();

                $stok = DB::table('transaksis')
                    ->where('invoice', 'like', "%-$tanggal-TLR-$kode")
                    ->first();

                if (!$hpp || !$stok) {
                    continue;
                }

                $detailsPenjualan = DB::table('detail_transaksis')->where('transaksi_id', $penjualan->id)->where('kategori_id', $KAT_PENJUALAN)->get();

                $totalBaru = 0;

                foreach ($detailsPenjualan as $dp) {
                    $detailHpp = DB::table('detail_transaksis')->where('transaksi_id', $hpp->id)->where('kategori_id', $KAT_HPP)->where('barang_id', $dp->barang_id)->first();

                    $detailStok = DB::table('detail_transaksis')->where('transaksi_id', $stok->id)->where('kategori_id', $KAT_STOK)->where('barang_id', $dp->barang_id)->first();

                    if (!$detailHpp || !$detailStok) {
                        continue;
                    }

                    $qty = $detailHpp->kuantitas;

                    $hargaJual = $dp->value;
                    $subtotalJual = $dp->sub_total;

                    $hargaHpp = $detailHpp->value;

                    /*
                |--------------------------------------------------------------------------
                | VALIDASI HPP
                |--------------------------------------------------------------------------
                */

                    // HPP per unit tidak boleh >= harga jual
                    if ($hargaHpp >= $hargaJual) {
                        $hargaHpp = $hargaJual - 100;
                    }

                    $hargaHpp = max($hargaHpp, 0);

                    $subtotalHpp = $hargaHpp * $qty;

                    // Jika subtotal masih lebih besar
                    if ($subtotalHpp >= $subtotalJual) {
                        $subtotalHpp = $subtotalJual - 100;

                        $hargaHpp = $subtotalHpp / max($qty, 1);
                    }

                    $subtotalHpp = max($subtotalHpp, 0);

                    /*
                |--------------------------------------------------------------------------
                | UPDATE HPP
                |--------------------------------------------------------------------------
                */

                    DB::table('detail_transaksis')
                        ->where('id', $detailHpp->id)
                        ->update([
                            'value' => round($hargaHpp, 2),
                            'sub_total' => round($subtotalHpp, 2),
                            'updated_at' => now(),
                        ]);

                    /*
                |--------------------------------------------------------------------------
                | UPDATE STOK
                |--------------------------------------------------------------------------
                */

                    DB::table('detail_transaksis')
                        ->where('id', $detailStok->id)
                        ->update([
                            'value' => round($hargaHpp, 2),
                            'sub_total' => round($subtotalHpp, 2),
                            'updated_at' => now(),
                        ]);

                    $totalBaru += $subtotalHpp;
                }

                /*
            |--------------------------------------------------------------------------
            | UPDATE TOTAL TRANSAKSI
            |--------------------------------------------------------------------------
            */

                DB::table('transaksis')
                    ->where('id', $hpp->id)
                    ->update([
                        'total' => round($totalBaru, 2),
                        'updated_at' => now(),
                    ]);

                DB::table('transaksis')
                    ->where('id', $stok->id)
                    ->update([
                        'total' => round($totalBaru, 2),
                        'updated_at' => now(),
                    ]);
            }
        });

        $this->success('Penyesuaian HPP & Stok berhasil 🔥', position: 'toast-top');
    }

    public function with(): array
    {
        return [
            'rows' => $this->data(),
            'headers' => $this->headers(),
        ];
    }
};
?>

<div>
    <x-header title="Audit Tanggal Stok Batch" separator>
        <x-slot:actions>
            <x-button label="Fix Semua" icon="o-wrench" class="btn-error" wire:click="fixAll" spinner />

            {{-- 🔥 BUTTON BARU --}}
            <x-button label="Adjust HPP" icon="o-calculator" class="btn-warning" wire:click="adjustHppFromPenjualan"
                spinner />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="$headers" :rows="$rows" with-pagination>

            {{-- STATUS --}}
            @scope('cell_status', $row)
                @php
                    $sinkron = $row->batch_tanggal == $row->trx_tanggal;
                @endphp

                @if ($sinkron)
                    <span class="text-green-600 font-bold">✔ Sinkron</span>
                @else
                    <span class="text-red-600 font-bold">❌ Tidak Sinkron</span>
                @endif
            @endscope

            {{-- AKSI --}}
            @scope('cell_aksi', $row)
                @php
                    $sinkron = $row->batch_tanggal == $row->trx_tanggal;
                @endphp

                @if (!$sinkron)
                    <x-button label="Fix" icon="o-wrench" class="btn-warning btn-sm"
                        wire:click="fixTanggal({{ $row->batch_id }})" />
                @else
                    <span class="text-gray-400">-</span>
                @endif
            @endscope

        </x-table>
    </x-card>
</div>
