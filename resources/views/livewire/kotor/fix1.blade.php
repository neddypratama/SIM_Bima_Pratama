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

    public function with(): array
    {
        return [
            'rows' => $this->data(),
            'headers' => $this->headers(),
        ];
    }

    public function fixAll(): void
    {
        DB::transaction(function () {
            $rows = DB::table('stok_batches as sb')->join('detail_transaksis as dt', 'dt.id', '=', 'sb.detail_transaksi_id')->join('transaksis as t', 't.id', '=', 'dt.transaksi_id')->select('sb.id as batch_id', 'sb.tanggal as batch_tanggal', 't.tanggal as trx_tanggal')->get();

            foreach ($rows as $row) {
                if ($row->batch_tanggal != $row->trx_tanggal) {
                    DB::table('stok_batches')
                        ->where('id', $row->batch_id)
                        ->update([
                            'tanggal' => $row->trx_tanggal,
                            'updated_at' => now(),
                        ]);
                    $this->success('Semua batch berhasil sinkron 🔥');
                } else {
                    $this->error('Semua batch sudah sinkron 🔥');
                }
            }
        });
    }
};
?>

<div>
    <x-header title="Audit Tanggal Stok Batch" separator>
        <x-slot:actions>
            <x-button label="Fix Semua" icon="o-wrench" class="btn-error" wire:click="fixAll" spinner/>
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
                        wire:click="fixTanggal({{ $row->batch_id }}, {{ $row->detail_id }})" />
                @else
                    <span class="text-gray-400">-</span>
                @endif
            @endscope

        </x-table>
    </x-card>
</div>
