<?php

use Livewire\Volt\Component;
use App\Models\Transaksi;
use App\Models\Stok;

new class extends Component {
    public Stok $stok;
    public ?Transaksi $kotor = null;
    public ?Transaksi $bentes = null;
    public ?Transaksi $ceplok = null;
    public ?Transaksi $pecah = null;
    public $telur = [];

    public function mount(Stok $stok): void
    {
        // Muat relasi stok utama
        $this->stok = $stok->load(['user', 'barang.jenis', 'barang']);

        // Ambil 4 digit terakhir dari invoice stok (misalnya "0001")
        $suffix = substr($stok->invoice, -4);
        $part = explode('-', $stok->invoice);
        $tanggal = $part[1];
        
        // Cari transaksi Telur Kotor (INV-...-KTR-xxxx)
        $this->kotor = Transaksi::with(['client', 'details.kategori', 'details.barang'])
            ->where('invoice', 'like', "INV-$tanggal-KTR-" . $suffix)
            ->first();
            
            $this->bentes = Transaksi::with(['client', 'details.kategori', 'details.barang'])
            ->where('invoice', 'like', "INV-$tanggal-BTS-" . $suffix)
            ->first();
            
            $this->ceplok = Transaksi::with(['client', 'details.kategori', 'details.barang'])
            ->where('invoice', 'like', "INV-$tanggal-CLK-" . $suffix)
            ->first();
            
        // Cari transaksi Telur Pecah (INV-...-PCH-xxxx)
        $this->pecah = Transaksi::with(['client', 'details.kategori', 'details.barang'])
        ->where('invoice', 'like', "INV-$tanggal-PRK-" . $suffix)
            ->first();
            
        // Cari semua transaksi telur yang berhubungan dengan stok ini
        $this->telur = Transaksi::with(['client', 'details.kategori', 'details.barang'])
        ->where('invoice', 'like', "INV-$tanggal-TLR%-" . $suffix)
        ->get();
        dd($this->kotor, $this->bentes, $this->ceplok, $this->pecah, $this->telur);
    }
};
?>

<div>
    <x-header title="Detail {{ $stok->invoice }}" separator progress-indicator />

    <x-card>
        {{-- Informasi Transaksi --}}
        <div class="p-7 mt-2 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="mb-3">Invoice</p>
                    <p class="font-semibold">{{ $stok->invoice }}</p>
                </div>
                <div>
                    <p class="mb-3">User</p>
                    <p class="font-semibold">{{ $stok->user?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Tanggal</p>
                    <p class="font-semibold">{{ \Carbon\Carbon::parse($stok->tanggal)->format('d-m-Y H:i') }}</p>
                </div>
            </div>
        </div>

        {{-- Informasi Client --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="mb-3">Nama Barang</p>
                    <p class="font-semibold">{{ $stok->barang?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Jenis Barang</p>
                    <p class="font-semibold">{{ $stok->barang->jenis?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Stok Sebelum</p>
                    <p class="font-semibold">
                        {{ $stok->barang?->stok - $stok->tambah + ($stok->kurang + $stok->kotor + $stok->rusak) ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Detail Barang --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <p class="mb-3 font-semibold">Detail Stok</p>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-3 rounded-lg p-5 ">
                <div>
                    <p class="mb-1 text-gray-500">Tambah</p>
                    <p class="font-semibold">{{ $stok->tambah ?? 0 }}</p>
                </div>
                <div>
                    <p class="mb-1 text-gray-500">Kurang</p>
                    <p class="font-semibold">{{ $stok->kurang ?? 0 }}</p>
                </div>
                <div>
                    <p class="mb-1 text-gray-500">Kotor</p>
                    <p class="font-semibold">{{ $stok->kotor }}</p>
                </div>
                <div>
                    <p class="mb-1 text-gray-500">Pecah</p>
                    <p class="font-semibold">{{ $stok->rusak }}
                    </p>
                </div>
                <div>
                    <p class="mb-1 text-gray-500">Stok Sekarang</p>
                    <p class="font-semibold">{{ $stok->barang?->stok ?? 0 }}</p>
                </div>
            </div>
        </div>
    </x-card>

    <x-header class="mt-3" title="Detail {{ $kotor->invoice }}" separator progress-indicator />

    <x-card>
        {{-- Informasi kotor --}}
        <div class="p-7 mt-2 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="mb-3">Invoice</p>
                    <p class="font-semibold">{{ $kotor->invoice }}</p>
                </div>
                <div>
                    <p class="mb-3">Rincian Transaksi</p>
                    <p class="font-semibold">{{ $kotor->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Tanggal</p>
                    <p class="font-semibold">{{ \Carbon\Carbon::parse($kotor->tanggal)->format('d-m-Y H:i') }}</p>
                </div>
            </div>
        </div>

        {{-- Informasi Client --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="mb-3">Nama Client</p>
                    <p class="font-semibold">{{ $kotor->client?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Alamat Client</p>
                    <p class="font-semibold">{{ $kotor->client?->alamat ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">User</p>
                    <p class="font-semibold">{{ $kotor->user?->name ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Detail Barang --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <p class="mb-3 font-semibold">Detail Barang</p>
            @forelse ($kotor->details as $detail)
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
                        <p class="font-semibold">Rp
                            {{ number_format($detail->value * $detail->kuantitas, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-gray-500">Kategori</p>
                        <p class="font-semibold">{{ $detail->kategori->name ?? '-' }}</p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Tidak ada detail barang untuk kotor ini.</p>
            @endforelse
        </div>

        {{-- Total --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <p class="mb-3">Grand Total</p>
            <p class="font-semibold text-end text-yellow-500 text-xl">
                Rp. {{ number_format($kotor->total, 0, ',', '.') }}
            </p>
        </div>
    </x-card>

    <x-header class="mt-3" title="Detail {{ $bentes->invoice }}" separator progress-indicator />

    <x-card>
        {{-- Informasi bentes --}}
        <div class="p-7 mt-2 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="mb-3">Invoice</p>
                    <p class="font-semibold">{{ $bentes->invoice }}</p>
                </div>
                <div>
                    <p class="mb-3">Rincian Transaksi</p>
                    <p class="font-semibold">{{ $bentes->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Tanggal</p>
                    <p class="font-semibold">{{ \Carbon\Carbon::parse($bentes->tanggal)->format('d-m-Y H:i') }}</p>
                </div>
            </div>
        </div>

        {{-- Informasi Client --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="mb-3">Nama Client</p>
                    <p class="font-semibold">{{ $bentes->client?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Alamat Client</p>
                    <p class="font-semibold">{{ $bentes->client?->alamat ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">User</p>
                    <p class="font-semibold">{{ $bentes->user?->name ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Detail Barang --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <p class="mb-3 font-semibold">Detail Barang</p>
            @forelse ($bentes->details as $detail)
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
                        <p class="font-semibold">Rp
                            {{ number_format($detail->value * $detail->kuantitas, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-gray-500">Kategori</p>
                        <p class="font-semibold">{{ $detail->kategori->name ?? '-' }}</p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Tidak ada detail barang untuk bentes ini.</p>
            @endforelse
        </div>

        {{-- Total --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <p class="mb-3">Grand Total</p>
            <p class="font-semibold text-end text-yellow-500 text-xl">
                Rp. {{ number_format($bentes->total, 0, ',', '.') }}
            </p>
        </div>
    </x-card>

    <x-header class="mt-3" title="Detail {{ $ceplok->invoice }}" separator progress-indicator />

    <x-card>
        {{-- Informasi ceplok --}}
        <div class="p-7 mt-2 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="mb-3">Invoice</p>
                    <p class="font-semibold">{{ $ceplok->invoice }}</p>
                </div>
                <div>
                    <p class="mb-3">Rincian Transaksi</p>
                    <p class="font-semibold">{{ $ceplok->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Tanggal</p>
                    <p class="font-semibold">{{ \Carbon\Carbon::parse($ceplok->tanggal)->format('d-m-Y H:i') }}</p>
                </div>
            </div>
        </div>

        {{-- Informasi Client --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="mb-3">Nama Client</p>
                    <p class="font-semibold">{{ $ceplok->client?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Alamat Client</p>
                    <p class="font-semibold">{{ $ceplok->client?->alamat ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">User</p>
                    <p class="font-semibold">{{ $ceplok->user?->name ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Detail Barang --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <p class="mb-3 font-semibold">Detail Barang</p>
            @forelse ($ceplok->details as $detail)
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
                        <p class="font-semibold">Rp
                            {{ number_format($detail->value * $detail->kuantitas, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-gray-500">Kategori</p>
                        <p class="font-semibold">{{ $detail->kategori->name ?? '-' }}</p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Tidak ada detail barang untuk ceplok ini.</p>
            @endforelse
        </div>

        {{-- Total --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <p class="mb-3">Grand Total</p>
            <p class="font-semibold text-end text-yellow-500 text-xl">
                Rp. {{ number_format($ceplok->total, 0, ',', '.') }}
            </p>
        </div>
    </x-card>

    <x-header class="mt-3" title="Detail {{ $pecah->invoice }}" separator progress-indicator />

    <x-card>
        {{-- Informasi pecah --}}
        <div class="p-7 mt-2 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="mb-3">Invoice</p>
                    <p class="font-semibold">{{ $pecah->invoice }}</p>
                </div>
                <div>
                    <p class="mb-3">Rincian Transaksi</p>
                    <p class="font-semibold">{{ $pecah->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Tanggal</p>
                    <p class="font-semibold">{{ \Carbon\Carbon::parse($pecah->tanggal)->format('d-m-Y H:i') }}</p>
                </div>
            </div>
        </div>

        {{-- Informasi Client --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="mb-3">Nama Client</p>
                    <p class="font-semibold">{{ $pecah->client?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">Alamat Client</p>
                    <p class="font-semibold">{{ $pecah->client?->alamat ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-3">User</p>
                    <p class="font-semibold">{{ $pecah->user?->name ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Detail Barang --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <p class="mb-3 font-semibold">Detail Barang</p>
            @forelse ($pecah->details as $detail)
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
                        <p class="font-semibold">Rp
                            {{ number_format($detail->value * $detail->kuantitas, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-gray-500">Kategori</p>
                        <p class="font-semibold">{{ $detail->kategori->name ?? '-' }}</p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Tidak ada detail barang untuk pecah ini.</p>
            @endforelse
        </div>

        {{-- Total --}}
        <div class="p-7 mt-4 rounded-lg shadow-md">
            <p class="mb-3">Grand Total</p>
            <p class="font-semibold text-end text-yellow-500 text-xl">
                Rp. {{ number_format($pecah->total, 0, ',', '.') }}
            </p>
        </div>
    </x-card>

    @foreach ($this->telur as $telur)
        <x-header class="mt-3" title="Detail {{ $telur->invoice }}" separator progress-indicator />

        <x-card>
            {{-- Informasi telur --}}
            <div class="p-7 mt-2 rounded-lg shadow-md">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="mb-3">Invoice</p>
                        <p class="font-semibold">{{ $telur->invoice }}</p>
                    </div>
                    <div>
                        <p class="mb-3">Rincian Transaksi</p>
                        <p class="font-semibold">{{ $telur->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="mb-3">Tanggal</p>
                        <p class="font-semibold">{{ \Carbon\Carbon::parse($telur->tanggal)->format('d-m-Y H:i') }}</p>
                    </div>
                </div>
            </div>

            {{-- Informasi Client --}}
            <div class="p-7 mt-4 rounded-lg shadow-md">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="mb-3">Nama Client</p>
                        <p class="font-semibold">{{ $telur->client?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="mb-3">Alamat Client</p>
                        <p class="font-semibold">{{ $telur->client?->alamat ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="mb-3">User</p>
                        <p class="font-semibold">{{ $telur->user?->name ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Detail Barang --}}
            <div class="p-7 mt-4 rounded-lg shadow-md">
                <p class="mb-3 font-semibold">Detail Barang</p>
                @forelse ($telur->details as $detail)
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
                            <p class="font-semibold">Rp
                                {{ number_format($detail->value * $detail->kuantitas, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="mb-1 text-gray-500">Kategori</p>
                            <p class="font-semibold">{{ $detail->kategori->name ?? '-' }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">Tidak ada detail barang untuk telur ini.</p>
                @endforelse
            </div>

            {{-- Total --}}
            <div class="p-7 mt-4 rounded-lg shadow-md">
                <p class="mb-3">Grand Total</p>
                <p class="font-semibold text-end text-yellow-500 text-xl">
                    Rp. {{ number_format($telur->total, 0, ',', '.') }}
                </p>
            </div>
        </x-card>
    @endforeach

    <div class="mt-6">
        <x-button label="Kembali" link="/stok-telur" />
    </div>
</div>
