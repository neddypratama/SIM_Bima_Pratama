<?php

use Livewire\Volt\Component;
use App\Models\Transaksi;

new class extends Component {
    public ?Transaksi $transaksi;
    public ?Transaksi $modal; // ubah dari single ke koleksi (array/kumpulan transaksi)

    public function mount(Transaksi $transaksi): void
    {
        // Muat transaksi utama lengkap
        $this->transaksi = $transaksi->load(['client', 'details.kategori', 'details.barang']);

        // Ambil suffix terakhir dari invoice (contoh: 3NCP)
        $suffix = substr($this->transaksi->invoice, -4);
        $part = explode('-', $transaksi->invoice);
        $tanggal = $part[1];
        
        $this->modal = Transaksi::where('invoice', 'like', "%-$tanggal-%-$suffix")->first();
        $this->modal = $this->modal->load(['client', 'details.kategori', 'details.barang']);
        // dd($this->modal, $tanggal);
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
                    <p class="mb-3">Nama Client</p>
                    <p class="font-semibold">{{ $transaksi->client?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Alamat Client</p>
                    <p class="font-semibold">{{ $transaksi->client?->alamat ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">User</p>
                    <p class="font-semibold">{{ $transaksi->user?->name ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Detail Barang --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <p class="mb-3 font-semibold">Detail Barang</p>
            @forelse ($transaksi->details as $detail)
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-3 rounded-lg p-5 ">
                    <div>
                        <p class="mb-1 text-gray-500">Barang</p>
                        <p class="font-semibold">{{ $detail->barang?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-gray-500">Qty</p>
                        <p class="font-semibold">{{ $detail->kuantitas }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-gray-500">Harga</p>
                        <p class="font-semibold">Rp {{ number_format($detail->value, 0, ',', '.') }}</p>
                    </div>

                    <div>
                        <p class="mb-1 text-gray-500">Total</p>
                        <p class="font-semibold">Rp {{ number_format($detail->sub_total, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-gray-500">Kategori</p>
                        <p class="font-semibold">{{ $detail->kategori?->name ?? '-' }}</p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Tidak ada detail barang untuk transaksi ini.</p>
            @endforelse
        </div>

        {{-- Total --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <p class="mb-3">Grand Total</p>
            <p class="font-semibold text-end text-yellow-500 text-xl">
                Rp. {{ number_format($transaksi->total, 0, ',', '.') }}
            </p>
        </div>
    </x-card>

    <x-header title="Detail {{ $modal->invoice }}" separator progress-indicator />

    <x-card>
        {{-- Informasi modal --}}
        <div class="p-7 mt-2 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="mb-3">Invoice</p>
                    <p class="font-semibold">{{ $modal->invoice }}</p>
                </div>
                <div>
                    <p class="mb-3">Rincian Modal</p>
                    <p class="font-semibold">{{ $modal->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Tanggal</p>
                    <p class="font-semibold">{{ \Carbon\Carbon::parse($modal->tanggal)->format('d-m-Y H:i') }}</p>
                </div>
            </div>
        </div>

        {{-- Informasi Client --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div>
                    <p class="mb-3">Nama Client</p>
                    <p class="font-semibold">{{ $modal->client?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Alamat Client</p>
                    <p class="font-semibold">{{ $modal->client?->alamat ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">User</p>
                    <p class="font-semibold">{{ $modal->user?->name ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Detail Barang --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <p class="mb-3 font-semibold">Detail Barang</p>
            @forelse ($modal->details as $detail)
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-3 rounded-lg p-5 ">
                    <div>
                        <p class="mb-1 text-gray-500">Barang</p>
                        <p class="font-semibold">{{ $detail->barang?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-gray-500">Qty</p>
                        <p class="font-semibold">{{ $detail->kuantitas }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-gray-500">Harga</p>
                        <p class="font-semibold">Rp {{ number_format($detail->value, 0, ',', '.') }}</p>
                    </div>

                    <div>
                        <p class="mb-1 text-gray-500">Total</p>
                        <p class="font-semibold">Rp {{ number_format($detail->sub_total, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-gray-500">Kategori</p>
                        <p class="font-semibold">{{ $detail->kategori?->name ?? '-' }}</p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Tidak ada detail barang untuk modal ini.</p>
            @endforelse
        </div>

        {{-- Total --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <p class="mb-3">Grand Total</p>
            <p class="font-semibold text-end text-yellow-500 text-xl">
                Rp. {{ number_format($modal->total, 0, ',', '.') }}
            </p>
        </div>
    </x-card>

    <div class="mt-6">
        <x-button label="Kembali" link="/tunai
        " />
    </div>
</div>
