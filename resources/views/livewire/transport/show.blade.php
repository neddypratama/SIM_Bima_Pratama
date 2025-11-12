<?php

use Livewire\Volt\Component;
use App\Models\Truk;

new class extends Component {
    public Truk $transaksi;

    public function mount(Truk $truk): void
    {
        $this->transaksi = Truk::findOrFail($truk->id)->load('client');
    }
};
?>

<div>
    <x-header title="Detail {{ $transaksi->invoice }}" separator progress-indicator />

    <x-card>
        {{-- Informasi Transaksi --}}
        <div class="p-7 mt-2 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="mb-3">Invoice</p>
                    <p class="font-semibold">{{ $transaksi->invoice }}</p>
                </div>
                <div>
                    <p class="mb-3">Rincian Transaksi</p>
                    <p class="font-semibold">{{ $transaksi->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Tanggal</p>
                    <p class="font-semibold">{{ \Carbon\Carbon::parse($transaksi->tanggal)->format('d-m-Y H:i') }}</p>
                </div>
            </div>
        </div>

        {{-- Informasi Client --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="mb-3">Nama Truk</p>
                    <p class="font-semibold">{{ $transaksi->client?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Nopol</p>
                    <p class="font-semibold">{{ $transaksi->client?->alamat ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Pengemudi</p>
                    <p class="font-semibold">{{ $transaksi->client?->keterangan ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Total --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <p class="mb-3">Grand Total</p>
            <p class="font-semibold text-end text-yellow-500 text-xl">
                Rp. {{ number_format($transaksi->total, 0, ',', '.') }}
            </p>
        </div>
    </x-card>

    <div class="mt-6">
        <x-button label="Kembali" link="/transport
        " />
    </div>
</div>
