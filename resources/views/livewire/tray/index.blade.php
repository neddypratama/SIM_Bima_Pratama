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
    public string $filterType = 'Kredit'; // ✅ Filter untuk tipe transaksi
    public array $sortBy = ['column' => 'tanggal', 'direction' => 'desc'];
    public $page = [['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100'], ['id' => 500, 'name' => '500']];

    public int $perPage = 25; // Default jumlah data per halaman
    public array $types = [['id' => 'Debit', 'name' => 'Pembelian'], ['id' => 'Kredit', 'name' => 'Penjualan']];

    public function clear(): void
    {
        $this->reset(['search', 'startDate', 'endDate', 'filterType']);
        $this->resetPage();
        $this->success('Filter dibersihkan.', position: 'toast-top');
    }

    public function headers(): array
    {
        return [['key' => 'nama_barang', 'label' => 'Nama Barang', 'class' => 'w-56'], ['key' => 'total_jumlah', 'label' => 'Jumlah', 'class' => 'w-48'], ['key' => 'total_harga', 'label' => 'Total Harga (Rp)', 'class' => 'w-48']];
    }

    /** 🔹 Query utama */
    public function pembelianTelur(): LengthAwarePaginator
    {
        return DB::table('barangs as barang')
            ->leftJoin('jenis_barangs as jenis', 'jenis.id', '=', 'barang.jenis_id')
            ->leftJoin('detail_transaksis', 'barang.id', '=', 'detail_transaksis.barang_id')
            ->leftJoin('transaksis as transaksi', 'detail_transaksis.transaksi_id', '=', 'transaksi.id')
            ->leftJoin('kategoris as kategori', 'kategori.id', '=', 'detail_transaksis.kategori_id')

            ->where('jenis.name', 'like', 'Tray%')
            ->select(
                'barang.id',
                'barang.name as nama_barang',

                DB::raw("
                SUM(
                    CASE
                        /* ================= PEMBELIAN TRAY ================= */
                        WHEN '{$this->filterType}' = 'Debit'
                            AND kategori.name LIKE '%Stok Tray%'
                            AND transaksi.type = 'Debit'
                            AND transaksi.invoice LIKE '%-TRY-%'
                        AND transaksi.status = 'Selesai'
                            THEN detail_transaksis.kuantitas

                        /* ============== RETUR PEMBELIAN TRAY ============== */
                        WHEN '{$this->filterType}' = 'Debit'
                            AND kategori.name LIKE '%Stok Tray%'
                            AND transaksi.type = 'Kredit'
                            AND transaksi.name LIKE 'Retur Pembelian dari%'
                        AND transaksi.status = 'Selesai'
                            THEN -detail_transaksis.kuantitas

                        /* ================= PENJUALAN TRAY ================= */
                        WHEN '{$this->filterType}' = 'Kredit'
                            AND kategori.name LIKE '%Penjualan EggTray%'
                            AND transaksi.type = 'Kredit'
                        AND transaksi.status = 'Selesai'
                            THEN detail_transaksis.kuantitas

                        /* ============== RETUR PENJUALAN TRAY ============== */
                        WHEN '{$this->filterType}' = 'Kredit'
                            AND kategori.name LIKE '%Penjualan EggTray%'
                            AND transaksi.type = 'Debit'
                            AND transaksi.name LIKE 'Retur Penjualan dari%'
                        AND transaksi.status = 'Selesai'
                            THEN -detail_transaksis.kuantitas

                        ELSE 0
                    END
                ) as total_jumlah
            "),

                DB::raw("
                SUM(
                    CASE
                        /* ================= PEMBELIAN TRAY ================= */
                        WHEN '{$this->filterType}' = 'Debit'
                            AND kategori.name LIKE '%Stok Tray%'
                            AND transaksi.type = 'Debit'
                            AND transaksi.invoice LIKE '%-TRY-%'
                        AND transaksi.status = 'Selesai'
                            THEN detail_transaksis.kuantitas * detail_transaksis.value

                        /* ============== RETUR PEMBELIAN TRAY ============== */
                        WHEN '{$this->filterType}' = 'Debit'
                            AND kategori.name LIKE '%Stok Tray%'
                            AND transaksi.type = 'Kredit'
                            AND transaksi.name LIKE 'Retur Pembelian dari%'
                        AND transaksi.status = 'Selesai'
                            THEN -(detail_transaksis.kuantitas * detail_transaksis.value)

                        /* ================= PENJUALAN TRAY ================= */
                        WHEN '{$this->filterType}' = 'Kredit'
                            AND kategori.name LIKE '%Penjualan EggTray%'
                            AND transaksi.type = 'Kredit'
                        AND transaksi.status = 'Selesai'
                            THEN detail_transaksis.kuantitas * detail_transaksis.value

                        /* ============== RETUR PENJUALAN TRAY ============== */
                        WHEN '{$this->filterType}' = 'Kredit'
                            AND kategori.name LIKE '%Penjualan EggTray%'
                            AND transaksi.type = 'Debit'
                            AND transaksi.name LIKE 'Retur Penjualan dari%'
                        AND transaksi.status = 'Selesai'
                            THEN -(detail_transaksis.kuantitas * detail_transaksis.value)

                        ELSE 0
                    END
                ) as total_harga
            "),
            )

            ->when($this->search, fn($q) => $q->where('barang.name', 'like', "%{$this->search}%"))

            ->when($this->startDate, fn($q) => $q->whereDate('transaksi.tanggal', '>=', $this->startDate))

            ->when($this->endDate, fn($q) => $q->whereDate('transaksi.tanggal', '<=', $this->endDate))

            ->groupBy('barang.id', 'barang.name')
            ->orderBy('barang.name', 'asc')
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        // dd( $this->pembelianTelur());
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
    <x-header title="Laporan Transaksi Tray" separator progress-indicator />

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
            <x-select label="Tipe Transaksi" :options="$types" wire:model.live="filterType" clearable />
        </div>

        <div class="md:col-span-2">
            <x-input placeholder="Cari nama barang..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />
        </div>
    </div>

    <x-card>
        <x-table :headers="$headers" :rows="$pembelianTelur" :sort-by="$sortBy" with-pagination>
            @scope('cell_nama_barang', $row)
                <span class="font-medium">
                    {{ $row->nama_barang }}
                </span>
            @endscope

            @scope('cell_total_jumlah', $row)
                <span class="font-bold text-blue-600">
                    {{ number_format($row->total_jumlah, 2, ',', '.') }}
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
