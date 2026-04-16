<?php

use Livewire\Volt\Component;
use App\Models\StokBatch;

new class extends Component {
    public StokBatch $stok;

    public function mount(StokBatch $batch): void
    {
        $this->stok = $batch->load(['barang', 'details.kategori', 'details.transaksi.client']);
    }
};
?>

<div>
    <x-header title="Detail {{ $stok->barang->name }}" separator progress-indicator />

    <x-card>

        {{-- Informasi StokBatch --}}
        <div class="p-7 mt-2 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="mb-3">Barang</p>
                    <p class="font-semibold">{{ $stok->barang->name }}</p>
                </div>
                <div>
                    <p class="mb-3">Tanggal</p>
                    <p class="font-semibold">{{ \Carbon\Carbon::parse($stok->tanggal)->format('d-m-Y H:i') }}</p>
                </div>
                <div>
                    <p class="mb-3">Qty</p>
                    <p class="font-semibold">{{ $stok->qty_masuk ?? 0 }}</p>
                </div>
                <div>
                    <p class="mb-1 text-gray-500">HPP</p>
                    <p class="font-semibold">Rp {{ number_format($stok->harga, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </x-card>

    <x-card class="mt-4">
        <h3 class="text-lg font-semibold mb-4">Detail Transaksi</h3>

        @if ($stok->details && $stok->details->transaksi)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">

                <div>
                    <p class="text-gray-500">Invoice</p>
                    <p class="font-semibold">
                        {{ $stok->details->transaksi->invoice }}
                    </p>
                </div>

                <div>
                    <p class="text-gray-500">Tanggal Transaksi</p>
                    <p class="font-semibold">
                        {{ \Carbon\Carbon::parse($stok->details->transaksi->tanggal)->format('d-m-Y H:i') }}
                    </p>
                </div>

                <div>
                    <p class="text-gray-500">Tipe</p>
                    <p class="font-semibold">
                        {{ ucfirst($stok->details->transaksi->type) }}
                    </p>
                </div>

                <div>
                    <p class="text-gray-500">Client</p>
                    <p class="font-semibold">
                        {{ $stok->details->transaksi->client->name ?? '-' }}
                    </p>
                </div>

            </div>
        @else
            <p class="text-gray-500">Tidak ada transaksi terkait</p>
        @endif
    </x-card>

    <div class="mt-6 flex gap-3">
        <x-button label="Kembali" link="/penambahan-stok" />
    </div>
</div>
