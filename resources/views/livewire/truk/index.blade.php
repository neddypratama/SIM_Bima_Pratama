<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Mary\Traits\Toast;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

new class extends Component {
    use Toast, WithPagination;

    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $search = '';
    public int $perPage = 10;
    public ?string $filterType = 'Pemasukan'; // Pemasukan / Pengeluaran

    public array $pages = [
        ['id' => 10, 'name' => '10'],
        ['id' => 25, 'name' => '25'],
        ['id' => 50, 'name' => '50'],
        ['id' => 100, 'name' => '100']
    ];

    public array $types = [
        ['id' => 'Pemasukan', 'name' => 'Pemasukan'],
        ['id' => 'Pengeluaran', 'name' => 'Pengeluaran'],
    ];

    public function clear(): void
    {
        $this->reset(['search', 'startDate', 'endDate', 'filterType']);
        $this->resetPage();
        $this->success('Filter dibersihkan.', position: 'toast-top');
    }

    public function headers(): array
    {
        return [
            ['key' => 'client_name', 'label' => 'Client', 'class' => 'w-64'],
            ['key' => 'nopol', 'label' => 'Nopol', 'class' => 'w-32 text-center'],
            ['key' => 'total_nilai', 'label' => 'Total Nilai (Rp)', 'class' => 'w-48 text-right'],
        ];
    }

    public function laporanTrukPerClient(): LengthAwarePaginator
    {
        return DB::table('truks as t')
            ->join('clients as c', 't.client_id', '=', 'c.id')
            
            // 🔹 Kondisi berdasarkan tipe transaksi
            ->when($this->filterType === 'Pemasukan', fn($q) => 
                $q->where('t.type', 'Kredit')
            )
            ->when($this->filterType === 'Pengeluaran', fn($q) => 
                $q->where('t.type', 'Debit')
            )
            
            // 🔹 Filter pencarian dan tanggal
            ->when($this->search, fn($q) => $q->where('c.name', 'like', "%{$this->search}%"))
            ->when($this->startDate, fn($q) => $q->whereDate('t.tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('t.tanggal', '<=', $this->endDate))
            
            // 🔹 Pilih data
            ->select(
                'c.name as client_name',
                'c.alamat as nopol',
                DB::raw('SUM(t.total) as total_nilai')
            )
            ->groupBy('c.name', 'c.alamat')
            ->orderByDesc('total_nilai')
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'laporan' => $this->laporanTrukPerClient(),
            'headers' => $this->headers(),
            'pages' => $this->pages,
            'types' => $this->types,
        ];
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Laporan Truk per Client" separator progress-indicator />

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
            <x-input placeholder="Cari nama client..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />
        </div>
    </div>

    <x-card>
        <x-table :headers="$headers" :rows="$laporan" with-pagination>
            @scope('cell_total_nilai', $row)
                <span class="font-semibold text-green-600">
                    Rp {{ number_format($row->total_nilai, 0, ',', '.') }}
                </span>
            @endscope
        </x-table>
    </x-card>
</div>
