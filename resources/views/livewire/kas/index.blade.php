<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Pagination\LengthAwarePaginator;

new class extends Component {
    use Toast, WithPagination;

    public string $search = '';
    public string $startDate = '';
    public string $endDate = '';
    public string $filterType = 'Debit'; // ✅ default ke Pemasukan
    public int $perPage = 10;

    public array $pages = [
        ['id' => 10, 'name' => '10'],
        ['id' => 25, 'name' => '25'],
        ['id' => 50, 'name' => '50'],
        ['id' => 100, 'name' => '100'],
    ];

    public array $types = [
        ['id' => 'Debit', 'name' => 'Pemasukan'],
        ['id' => 'Kredit', 'name' => 'Pengeluaran'],
    ];

    public function headers(): array
    {
        return [
            ['key' => 'kategori_kas', 'label' => 'Kategori Kas', 'class' => 'w-64'],
            ['key' => 'total_transaksi', 'label' => 'Total Transaksi (Rp)', 'class' => 'w-56 text-right'],
        ];
    }

    /** 🔹 Query utama laporan kas */
    public function laporanKas(): LengthAwarePaginator
    {
        return DB::table('transaksis as t')
            ->join('detail_transaksis as d', 't.id', '=', 'd.transaksi_id')
            ->join('kategoris as k', 'd.kategori_id', '=', 'k.id')
            ->select(
                'k.name as kategori_kas',
                DB::raw('COALESCE(SUM(t.total), 0) as total_transaksi')
            )
            ->whereIn('k.name', [
                'Kas Tunai',
                'Bank BCA Binti Wasilah',
                'Bank BCA Masduki',
                'Bank BRI Binti Wasilah',
                'Bank BRI Masduki',
                'Bank BNI Binti Wasilah',
                'Bank BNI Bima Pratama',
            ])
            ->when($this->filterType, fn($q) => $q->where('t.type', $this->filterType))
            ->when($this->search, fn($q) => $q->where('t.keterangan', 'like', "%{$this->search}%"))
            ->when($this->startDate, fn($q) => $q->whereDate('t.tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('t.tanggal', '<=', $this->endDate))
            ->groupBy('k.name')
            ->orderBy('k.name', 'asc')
            ->paginate($this->perPage);
    }

    /** 🔹 Total semua kas */
    public function totalKas(): float
    {
        return DB::table('transaksis as t')
            ->join('detail_transaksis as d', 't.id', '=', 'd.transaksi_id')
            ->join('kategoris as k', 'd.kategori_id', '=', 'k.id')
            ->whereIn('k.name', [
                'Kas Tunai',
                'Bank BCA Binti Wasilah',
                'Bank BCA Masduki',
                'Bank BRI Binti Wasilah',
                'Bank BRI Masduki',
                'Bank BNI Binti Wasilah',
                'Bank BNI Bima Pratama',
            ])
            ->when($this->filterType, fn($q) => $q->where('t.type', $this->filterType))
            ->when($this->startDate, fn($q) => $q->whereDate('t.tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('t.tanggal', '<=', $this->endDate))
            ->sum(DB::raw('t.total'));
    }

    public function with(): array
    {
        return [
            'laporanKas' => $this->laporanKas(),
            'headers' => $this->headers(),
            'pages' => $this->pages,
            'types' => $this->types,
            'totalKas' => $this->totalKas(),
        ];
    }

    public function clear(): void
    {
        $this->reset(['search', 'startDate', 'endDate', 'filterType']);
        $this->resetPage();
        $this->success('Filter dibersihkan.', position: 'toast-top');
    }

    public function updated($property): void
    {
        if (!is_array($property)) {
            $this->resetPage();
        }
    }
};
?>

<div>
    <x-header title="Laporan Kas" separator progress-indicator />

    <!-- 🔹 Filter -->
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
            <x-input placeholder="Cari keterangan..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </div>
    </div>

    <!-- 🔹 Tabel Laporan -->
    <x-card>
        <x-table :headers="$headers" :rows="$laporanKas" with-pagination>
            @scope('cell_kategori_kas', $row)
                <span class="font-semibold">
                    {{ $row->kategori_kas }}
                </span>
            @endscope

            @scope('cell_total_transaksi', $row)
                <div class="text-right font-bold text-green-600">
                    Rp {{ number_format($row->total_transaksi, 2, ',', '.') }}
                </div>
            @endscope
        </x-table>

        <div class="text-right mt-4 font-bold text-lg text-blue-700">
            Total {{ $filterType === 'Debit' ? 'Pemasukan' : 'Pengeluaran' }}: Rp {{ number_format($totalKas, 2, ',', '.') }}
        </div>
    </x-card>
</div>
