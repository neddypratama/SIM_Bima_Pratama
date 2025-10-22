<?php

use App\Models\DetailTransaksi;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

new class extends Component {
    use Toast, WithPagination;

    public string $search = '';
    public string $startDate = '';
    public string $endDate = '';
    public string $filterType = ''; // ✅ Filter untuk tipe transaksi
    public array $sortBy = ['column' => 'tanggal', 'direction' => 'desc'];
    public int $perPage = 10;

    public array $page = [['id' => 10, 'name' => '10'], ['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100']];

    public array $types = [['id' => 'Debit', 'name' => 'Pembelian'], ['id' => 'Kredit', 'name' => 'Penjualan']];

    public function clear(): void
    {
        $this->reset(['search', 'startDate', 'endDate', 'filterType']);
        $this->resetPage();
        $this->success('Filter dibersihkan.', position: 'toast-top');
    }

    public function headers(): array
    {
        return [['key' => 'tanggal', 'label' => 'Tanggal', 'class' => 'w-32'], ['key' => 'nama_barang', 'label' => 'Nama Barang', 'class' => 'w-56'], ['key' => 'total_jumlah', 'label' => 'Jumlah (kg)', 'class' => 'w-48'], ['key' => 'total_harga', 'label' => 'Total Harga (Rp)', 'class' => 'w-48']];
    }

    /** 🔹 Query utama */
    public function pembelianTelur(): LengthAwarePaginator
    {
        return DetailTransaksi::query()
            ->select(DB::raw('DATE(transaksi.tanggal) as tanggal'), DB::raw('barang.name as nama_barang'), DB::raw('SUM(detail_transaksis.kuantitas) as total_jumlah'), DB::raw('SUM(detail_transaksis.kuantitas * detail_transaksis.value) as total_harga'))
            ->join('transaksis as transaksi', 'detail_transaksis.transaksi_id', '=', 'transaksi.id')
            ->join('barangs as barang', 'barang.id', '=', 'detail_transaksis.barang_id')
            ->join('kategoris as kategori', 'kategori.id', '=', 'detail_transaksis.kategori_id')
            ->where('kategori.name', 'like', '%Stok Telur%')
            ->where('transaksi.invoice', 'like', '%-TLR-%')
            ->when($this->filterType, fn($q) => $q->where('transaksi.type', $this->filterType))
            ->when($this->search, fn($q) => $q->where('barang.name', 'like', "%{$this->search}%"))
            ->when($this->startDate, fn($q) => $q->whereDate('transaksi.tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('transaksi.tanggal', '<=', $this->endDate))
            ->groupBy('tanggal', 'barang.name')
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'pembelianTelur' => $this->pembelianTelur(),
            'headers' => $this->headers(),
            'pages' => $this->page,
            'types' => $this->types,
        ];
    }

    public function updated($property): void
    {
        if (!is_array($property) && $property != '') {
            $this->resetPage();
        }
    }
};

?>

<div>
    <x-header title="Laporan Transaksi Telur" separator progress-indicator />

    <div class="grid grid-cols-1 md:grid-cols-10 gap-4 items-end mb-4">
        <div class="md:col-span-2">
            <x-select label="Show entries" :options="$pages" wire:model.live="perPage" />
        </div>

        <div class="md:col-span-2">
            <x-input label="Tanggal Awal" type="date" wire:model.live="startDate" />
        </div>

        <div class="md:col-span-2">
            <x-input label="Tanggal Akhir" type="date" wire:model.live="endDate" />
        </div>

        <div class="md:col-span-2">
            <x-select label="Tipe Transaksi" placeholder="Semua" :options="$types" wire:model.live="filterType"
                clearable />
        </div>

        <div class="md:col-span-2">
            <x-input placeholder="Cari nama barang..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />
        </div>
    </div>

    <x-card>
        <x-table :headers="$headers" :rows="$pembelianTelur" :sort-by="$sortBy" with-pagination>
            @scope('cell_nama_barang', $row)
                <span class="font-medium text-gray-800">
                    {{ $row->nama_barang }}
                </span>
            @endscope

            @scope('cell_total_jumlah', $row)
                <span class="font-bold text-blue-600">
                    {{ number_format($row->total_jumlah, 2, ',', '.') }} kg
                </span>
            @endscope

            @scope('cell_total_harga', $row)
                <span class="font-bold text-green-600">
                    Rp {{ number_format($row->total_harga, 2, ',', '.') }}
                </span>
            @endscope
        </x-table>
    </x-card>
</div>
