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

            if (!$KAT_HPP) {
                $this->error('Kategori HPP tidak ditemukan!');
                return;
            }

            // Ambil semua transaksi HPP bertipe Debit yang selesai
            $hppTrxs = DB::table('transaksis')
                ->where('invoice', 'like', '%-HPP-%')
                ->where('type', 'Debit')
                ->where('status', 'Selesai')
                ->get(['id', 'invoice', 'total']);

            $totalAdjustedTrx = 0;
            $totalAdjustedItems = 0;

            foreach ($hppTrxs as $hpp) {
                // 1. Cari pasangan transaksi penjualan (DPT atau BON)
                $saleInv = str_replace('-HPP-', '-DPT-', $hpp->invoice);
                $sale = DB::table('transaksis')->where('invoice', $saleInv)->first(['id', 'invoice', 'total']);
                if (!$sale) {
                    $saleInv = str_replace('-HPP-', '-BON-', $hpp->invoice);
                    $sale = DB::table('transaksis')->where('invoice', $saleInv)->first(['id', 'invoice', 'total']);
                }

                if (!$sale) {
                    continue;
                }

                // 2. Cari pasangan transaksi stok keluar (TLR, STR, OBT, TRY)
                $stok = null;
                foreach (['-TLR-', '-STR-', '-OBT-', '-TRY-'] as $stokPrefix) {
                    $stokInv = str_replace('-HPP-', $stokPrefix, $hpp->invoice);
                    $stok = DB::table('transaksis')->where('invoice', $stokInv)->first(['id', 'invoice', 'total']);
                    if ($stok) {
                        break;
                    }
                }

                $saleDetails = DB::table('detail_transaksis')->where('transaksi_id', $sale->id)->orderBy('id')->get();
                $hppDetails = DB::table('detail_transaksis')->where('transaksi_id', $hpp->id)->where('kategori_id', $KAT_HPP)->orderBy('id')->get();
                $stokDetails = $stok ? DB::table('detail_transaksis')->where('transaksi_id', $stok->id)->orderBy('id')->get() : collect();

                $trxModified = false;
                $newHppTotal = 0;

                // Cek apakah jumlah item sama persis dan barangnya urut 1-to-1
                $directMatch = ($saleDetails->count() == $hppDetails->count());
                if ($directMatch) {
                    for ($i = 0; $i < $saleDetails->count(); $i++) {
                        if ($saleDetails[$i]->barang_id != $hppDetails[$i]->barang_id) {
                            $directMatch = false;
                            break;
                        }
                    }
                }

                if ($directMatch) {
                    for ($i = 0; $i < $hppDetails->count(); $i++) {
                        $hd = $hppDetails[$i];
                        $sd = $saleDetails[$i];
                        $std = $stokDetails->get($i);

                        $hargaJual = (float) $sd->value;
                        $subtotalJual = (float) $sd->sub_total;
                        $hargaHpp = (float) $hd->value;
                        $subtotalHpp = (float) $hd->sub_total;
                        $qty = (float) $hd->kuantitas;

                        if ($hargaHpp > $hargaJual || $subtotalHpp > $subtotalJual) {
                            $margin = ($hargaJual > 1000) ? 100 : round($hargaJual * 0.02, 2);
                            $hargaHpp = max($hargaJual - $margin, 0);
                            $subtotalHpp = round($hargaHpp * $qty, 2);

                            if ($subtotalHpp > $subtotalJual) {
                                $subtotalHpp = max($subtotalJual - $margin, 0);
                                $hargaHpp = $qty > 0 ? round($subtotalHpp / $qty, 2) : 0;
                            }

                            DB::table('detail_transaksis')->where('id', $hd->id)->update([
                                'value' => round($hargaHpp, 2),
                                'sub_total' => round($subtotalHpp, 2),
                                'updated_at' => now(),
                            ]);

                            if ($std) {
                                DB::table('detail_transaksis')->where('id', $std->id)->update([
                                    'value' => round($hargaHpp, 2),
                                    'sub_total' => round($subtotalHpp, 2),
                                    'updated_at' => now(),
                                ]);
                            }

                            $trxModified = true;
                            $totalAdjustedItems++;
                        }

                        $newHppTotal += $subtotalHpp;
                    }
                } else {
                    // Pool matching by barang_id dan kuantitas
                    $poolSale = $saleDetails->values()->all();
                    $poolStok = $stokDetails->values()->all();

                    foreach ($hppDetails as $hd) {
                        $matchedSale = null;
                        $matchedSaleIdx = null;
                        foreach ($poolSale as $idx => $sd) {
                            if ($sd->barang_id == $hd->barang_id && abs($sd->kuantitas - $hd->kuantitas) < 0.001) {
                                $matchedSale = $sd;
                                $matchedSaleIdx = $idx;
                                break;
                            }
                        }

                        $matchedStok = null;
                        $matchedStokIdx = null;
                        foreach ($poolStok as $idx => $std) {
                            if ($std->barang_id == $hd->barang_id && abs($std->kuantitas - $hd->kuantitas) < 0.001) {
                                $matchedStok = $std;
                                $matchedStokIdx = $idx;
                                break;
                            }
                        }

                        $hargaHpp = (float) $hd->value;
                        $subtotalHpp = (float) $hd->sub_total;
                        $qty = (float) $hd->kuantitas;

                        if ($matchedSale) {
                            unset($poolSale[$matchedSaleIdx]);
                            if ($matchedStokIdx !== null) {
                                unset($poolStok[$matchedStokIdx]);
                            }

                            $hargaJual = (float) $matchedSale->value;
                            $subtotalJual = (float) $matchedSale->sub_total;

                            if ($hargaHpp > $hargaJual || $subtotalHpp > $subtotalJual) {
                                $margin = ($hargaJual > 1000) ? 100 : round($hargaJual * 0.02, 2);
                                $hargaHpp = max($hargaJual - $margin, 0);
                                $subtotalHpp = round($hargaHpp * $qty, 2);

                                if ($subtotalHpp > $subtotalJual) {
                                    $subtotalHpp = max($subtotalJual - $margin, 0);
                                    $hargaHpp = $qty > 0 ? round($subtotalHpp / $qty, 2) : 0;
                                }

                                DB::table('detail_transaksis')->where('id', $hd->id)->update([
                                    'value' => round($hargaHpp, 2),
                                    'sub_total' => round($subtotalHpp, 2),
                                    'updated_at' => now(),
                                ]);

                                if ($matchedStok) {
                                    DB::table('detail_transaksis')->where('id', $matchedStok->id)->update([
                                        'value' => round($hargaHpp, 2),
                                        'sub_total' => round($subtotalHpp, 2),
                                        'updated_at' => now(),
                                    ]);
                                }

                                $trxModified = true;
                                $totalAdjustedItems++;
                            }
                        }

                        $newHppTotal += $subtotalHpp;
                    }
                }

                if ($trxModified) {
                    $totalAdjustedTrx++;

                    DB::table('transaksis')->where('id', $hpp->id)->update([
                        'total' => round($newHppTotal, 2),
                        'updated_at' => now(),
                    ]);

                    if ($stok) {
                        DB::table('transaksis')->where('id', $stok->id)->update([
                            'total' => round($newHppTotal, 2),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            $this->success("Penyesuaian HPP & Stok berhasil 🔥 ($totalAdjustedTrx transaksi, $totalAdjustedItems item)", position: 'toast-top');
        });
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
