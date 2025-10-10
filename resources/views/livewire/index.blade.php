<?php

namespace App\Livewire;

use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\User;
use App\Models\JenisBarang;
use App\Models\Kategori;
use App\Models\Barang;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Carbon\Carbon;

new class extends Component {
    use Toast;

    public string $period = 'month';
    public $startDate;
    public $endDate;
    public array $pendapatanChart = [];
    public array $pengeluaranChart = [];
    public array $stokTelurChart = [];
    public array $stokSentratChart = [];
    public array $stokObatChart = [];
    public array $stokTrayChart = [];

    public ?int $selectedKategoriPendapatan = null;
    public array $kategoriPendapatanList = [];

    public ?int $selectedKategoriPengeluaran = null;
    public array $kategoriPengeluaranList = [];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->kategoriPendapatanList = Kategori::where('type', 'Pendapatan')->get()->toArray();
        $this->kategoriPengeluaranList = Kategori::where('type', 'Pengeluaran')->get()->toArray();
        $this->setDefaultDates();
        $this->chartPendapatan();
        $this->chartPengeluaran();
        $this->chartStokTelur();
        $this->chartStokSentrat();
        $this->chartStokTray();
        $this->chartStokObat();
    }

    protected function setDefaultDates()
    {
        $now = Carbon::now();

        switch ($this->period) {
            case 'today':
                $this->startDate = $now->copy()->startOfDay();
                $this->endDate = $now->copy()->endOfDay();
                break;
            case 'week':
                $this->startDate = $now->copy()->startOfWeek();
                $this->endDate = $now->copy()->endOfWeek();
                break;
            case 'month':
                $this->startDate = $now->copy()->startOfMonth();
                $this->endDate = $now->copy()->endOfMonth();
                break;
            case 'year':
                $this->startDate = $now->copy()->startOfYear();
                $this->endDate = $now->copy()->endOfYear();
                break;
            default:
                $this->startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : $now->copy()->startOfMonth();
                $this->endDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : $now->copy()->endOfMonth();
        }
    }

    public function updatedPeriod()
    {
        $this->setDefaultDates();
        $this->chartPendapatan();
        $this->chartPengeluaran();
        $this->chartStokTelur();
        $this->chartStokSentrat();
        $this->chartStokTray();
        $this->chartStokObat();
    }

    public function applyDateRange()
    {
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $this->period = 'custom';
        $this->startDate = Carbon::parse($this->startDate)->startOfDay();
        $this->endDate = Carbon::parse($this->endDate)->endOfDay();

        $this->chartPendapatan();
        $this->chartPengeluaran();
        $this->chartStokTelur();
        $this->chartStokSentrat();
        $this->chartStokTray();
        $this->chartStokObat();
        $this->toast('Periode tanggal berhasil diperbarui', 'success');
    }

    public function chartPendapatan()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $query = Transaksi::with('kategori')
            ->whereBetween('tanggal', [$start, $end])
            ->whereHas('kategori', fn($q) => $q->where('type', 'Pendapatan'));

        // Jika kategori tertentu dipilih
        if ($this->selectedKategoriPendapatan) {
            $query->where('kategori_id', $this->selectedKategoriPendapatan);
        }

        $transactions = $query->orderBy('tanggal')->get();

        // Kelompokkan per tanggal
        $grouped = $transactions->groupBy(fn($trx) => Carbon::parse($trx->tanggal)->format('Y-m-d'));

        // Buat array tanggal unik
        $dates = collect();
        $period = \Carbon\CarbonPeriod::create($start, $end);
        foreach ($period as $date) {
            $dates->push($date->format('Y-m-d'));
        }

        // Siapkan data pendapatan
        $incomeData = [];
        foreach ($dates as $date) {
            $dayTransactions = $grouped->get($date, collect());
            $income = $dayTransactions->sum('total');
            $incomeData[] = $income;
        }

        // Buat chart
        $this->pendapatanChart = [
            'type' => 'line',
            'data' => [
                'labels' => $dates->toArray(),
                'datasets' => [
                    [
                        'label' => $this->selectedKategoriPendapatan ? 'Pendapatan: ' . Kategori::find($this->selectedKategoriPendapatan)?->name : 'Semua Pendapatan',
                        'data' => $incomeData,
                        'borderColor' => '#4CAF50',
                        'backgroundColor' => 'rgba(76, 175, 80, 0.2)',
                        'fill' => true,
                        'tension' => 0.3,
                    ],
                ],
            ],
        ];
    }

    public function chartPengeluaran()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $query = Transaksi::with('kategori')
            ->whereBetween('tanggal', [$start, $end])
            ->whereHas('kategori', fn($q) => $q->where('type', 'Pengeluaran'));

        // Jika kategori tertentu dipilih
        if ($this->selectedKategoriPengeluaran) {
            $query->where('kategori_id', $this->selectedKategoriPengeluaran);
        }

        $transactions = $query->orderBy('tanggal')->get();

        // Kelompokkan per tanggal
        $grouped = $transactions->groupBy(fn($trx) => Carbon::parse($trx->tanggal)->format('Y-m-d'));

        // Buat array tanggal unik
        $dates = collect();
        $period = \Carbon\CarbonPeriod::create($start, $end);
        foreach ($period as $date) {
            $dates->push($date->format('Y-m-d'));
        }

        // Siapkan data Pengeluaran
        $incomeData = [];
        foreach ($dates as $date) {
            $dayTransactions = $grouped->get($date, collect());
            $income = $dayTransactions->sum('total');
            $incomeData[] = $income;
        }

        // Buat chart
        $this->pengeluaranChart = [
            'type' => 'line',
            'data' => [
                'labels' => $dates->toArray(),
                'datasets' => [
                    [
                        'label' => $this->selectedKategoriPengeluaran ? 'Pengeluaran: ' . Kategori::find($this->selectedKategoriPengeluaran)?->name : 'Semua Pengeluaran',
                        'data' => $incomeData,
                        'borderColor' => '#F44336',
                        'backgroundColor' => 'rgba(244, 67, 54, 0.2)',
                        'fill' => true,
                        'tension' => 0.3,
                    ],
                ],
            ],
        ];
    }

    public function chartStokTelur()
    {
        $telurIds = JenisBarang::where('name', 'like', 'Telur%')->pluck('id');
        $this->stokTelurChart = $this->generateChartDataPie($telurIds, 'Stok Telur');
    }

    /**
     * Chart stok untuk jenis barang "Sentrat"
     */
    public function chartStokSentrat()
    {
        $sentratIds = JenisBarang::where('name', 'like', '%Sentrat%')->pluck('id');
        $this->stokSentratChart = $this->generateChartDataBar($sentratIds, 'Stok Sentrat');
    }

    /**
     * Chart stok untuk jenis barang "Obat"
     */
    public function chartStokObat()
    {
        $obatIds = JenisBarang::where('name', 'like', '%Obat%')->pluck('id');
        $this->stokObatChart = $this->generateChartDataBar($obatIds, 'Stok Obat');
    }

    /**
     * Chart stok untuk jenis barang "Tray"
     */
    public function chartStokTray()
    {
        $trayIds = JenisBarang::where('name', 'like', '%Tray%')->pluck('id');
        $this->stokTrayChart = $this->generateChartDataPie($trayIds, 'Stok Tray');
    }

    /**
     * Fungsi helper untuk membuat chart data dari kumpulan jenis barang
     */
    private function generateChartDataPie($jenisIds, $judul)
    {
        if ($jenisIds->isEmpty()) {
            return [];
        }

        $barangs = Barang::select('id', 'name', 'stok')->where('stok', '>', 0)->whereIn('jenis_id', $jenisIds)->get();

        if ($barangs->isEmpty()) {
            return [];
        }

        $grouped = $barangs->groupBy(fn($b) => $b->name);
        $data = $grouped->map(fn($items) => $items->sum('stok'))->toArray();

        $colors = collect($data)->map(fn() => sprintf('#%06X', mt_rand(0, 0xffffff)))->values()->toArray();

        return [
            'type' => 'pie',
            'data' => [
                'labels' => array_keys($data),
                'datasets' => [
                    [
                        'label' => $judul,
                        'data' => array_values($data),
                        'backgroundColor' => $colors,
                        'borderWidth' => 1,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => $judul,
                    ],
                ],
            ],
        ];
    }

    private function generateChartDataBar($jenisIds, string $judul): array
    {
        if ($jenisIds->isEmpty()) {
            return [];
        }

        $barangs = Barang::select('id', 'name', 'stok')->where('stok', '>', 0)->whereIn('jenis_id', $jenisIds)->get();

        if ($barangs->isEmpty()) {
            return [];
        }

        $grouped = $barangs->groupBy(fn($b) => $b->name);
        $data = $grouped->map(fn($items) => $items->sum('stok'))->toArray();

        $colors = collect($data)->map(fn() => sprintf('#%06X', mt_rand(0, 0xffffff)))->values()->toArray();

        $labels = array_keys($data);

        // 🟢 Perbaikan bagian label — setiap barang jadi satu dataset sendiri
        $datasets = [];
        $index = 0;
        foreach ($data as $namaBarang => $stok) {
            $datasets[] = [
                'label' => $namaBarang, // ✅ label berbeda per barang
                'data' => [$stok],
                'backgroundColor' => $colors[$index] ?? '#4CAF50',
                'borderWidth' => 1,
            ];
            $index++;
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => [$judul], // cuma satu label utama (judul kategori)
                'datasets' => $datasets, // ✅ tiap barang punya label unik
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => $judul,
                    ],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                    ],
                ],
            ],
        ];
    }

    public function incomeTotal(): float
    {
        return Transaksi::whereHas('kategori', fn($q) => $q->where('type', 'Pendapatan'))
            ->whereBetween('tanggal', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
            ->sum('total');
    }

    public function expenseTotal(): int
    {
        return Transaksi::whereHas('kategori', fn($q) => $q->where('type', 'Pengeluaran'))
            ->whereBetween('tanggal', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
            ->sum('total');
    }

    public function assetTotal(): int
    {
        $transaksis = Transaksi::whereHas('kategori', fn($q) => $q->where('type', 'Aset'))
            ->whereBetween('tanggal', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
            ->get();

        $totalDebit = $transaksis->where('type', 'Debit')->sum('total');
        $totalKredit = $transaksis->where('type', 'Kredit')->sum('total');

        return $totalDebit - $totalKredit;
    }

    public function liabiliatsTotal(): int
    {
        $transaksis = Transaksi::whereHas('kategori', fn($q) => $q->where('type', 'Liabilitas'))
            ->whereBetween('tanggal', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
            ->get();

        $totalDebit = $transaksis->where('type', 'Debit')->sum('total');
        $totalKredit = $transaksis->where('type', 'Kredit')->sum('total');

        return $totalKredit - $totalDebit;
    }

    public function updatedSelectedKategoriPendapatan()
    {
        $this->chartPendapatan();
    }

    public function updatedSelectedKategoriPengeluaran()
    {
        $this->chartPengeluaran();
    }

    public function with()
    {
        return [
            'incomeTotal' => $this->incomeTotal(),
            'expenseTotal' => $this->expenseTotal(),
            'assetTotal' => $this->assetTotal(),
            'liabiliatsTotal' => $this->liabiliatsTotal(),
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ];
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Dashboard" separator progress-indicator>
        <x-slot:actions>
            @php
                $periods = [
                    [
                        'id' => 'today',
                        'name' => 'Hari Ini',
                        'hint' => 'Data dalam 24 jam terakhir',
                        'icon' => 'o-clock',
                    ],
                    [
                        'id' => 'week',
                        'name' => 'Minggu Ini',
                        'hint' => 'Data minggu berjalan',
                        'icon' => 'o-calendar-days',
                    ],
                    ['id' => 'month', 'name' => 'Bulan Ini', 'hint' => 'Data bulan berjalan', 'icon' => 'o-chart-pie'],
                    ['id' => 'year', 'name' => 'Tahun Ini', 'hint' => 'Data tahun berjalan', 'icon' => 'o-chart-bar'],
                    [
                        'id' => 'custom',
                        'name' => 'Custom',
                        'hint' => 'Pilih rentang tanggal khusus',
                        'icon' => 'o-calendar',
                    ],
                ];
            @endphp

            <div class="flex flex-col gap-4">
                <x-select wire:model.live="period" :options="$periods" option-label="name" option-value="id"
                    option-description="hint" class="w-full" />

                @if ($period === 'custom')
                    <form wire:submit.prevent="applyDateRange" class="space-y-3">
                        <div class="flex flex-col md:flex-row gap-3">
                            <x-input type="date" label="Dari Tanggal" wire:model="startDate" :max="now()->format('Y-m-d')"
                                class="flex-1" />
                            <x-input type="date" label="Sampai Tanggal" wire:model="endDate" :min="$startDate"
                                :max="now()->format('Y-m-d')" class="flex-1" />
                            <x-button spinner label="Terapkan" type="submit" icon="o-check"
                                class="btn-primary w-full md:w-auto" />
                        </div>

                        @error('endDate')
                            <div class="text-red-500 text-sm">{{ $message }}</div>
                        @enderror

                        <div class="text-sm text-gray-500">
                            Periode terpilih:
                            {{ $startDate->translatedFormat('d M Y') }} - {{ $endDate->translatedFormat('d M Y') }}
                        </div>
                    </form>
                @endif
            </div>
        </x-slot:actions>
    </x-header>

    <!-- GRID UTAMA -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Pendapatan -->
        <x-card class="rounded-lg shadow p-4 flex items-center gap-3">
            <x-icon name="fas.money-bill-wave" class="text-purple-500 w-10 h-10 shrink-0" />
            <div>
                <p class="text-sm text-gray-600">Pendapatan</p>
                <p class="text-xl font-bold">Rp. {{ number_format($incomeTotal) }}</p>
            </div>
        </x-card>

        <!-- Pengeluaran -->
        <x-card class="rounded-lg shadow p-4 flex items-center gap-3">
            <x-icon name="fas.shopping-bag" class="text-blue-500 w-10 h-10 shrink-0" />
            <div>
                <p class="text-sm text-gray-600">Pengeluaran</p>
                <p class="text-xl font-bold">Rp. {{ number_format($expenseTotal) }}</p>
            </div>
        </x-card>

        <!-- Aset -->
        <x-card class="rounded-lg shadow p-4 flex items-center gap-3">
            <x-icon name="fas.sack-dollar" class="text-green-500 w-10 h-10 shrink-0" />
            <div>
                <p class="text-sm text-gray-600">Aset</p>
                <p class="text-xl font-bold">Rp. {{ number_format($assetTotal) }}</p>
            </div>
        </x-card>

        <!-- Liabilitas -->
        <x-card class="rounded-lg shadow p-4 flex items-center gap-3">
            <x-icon name="fas.money-check-dollar" class="text-yellow-500 w-10 h-10 shrink-0" />
            <div>
                <p class="text-sm text-gray-600">Liabilitas</p>
                <p class="text-xl font-bold">Rp. {{ number_format($liabiliatsTotal) }}</p>
            </div>
        </x-card>
    </div>

    <!-- CHARTS -->
    <div class="grid grid-cols-1 lg:grid-cols-10 gap-4">
        <x-card class="col-span-10 overflow-x-auto">
            <x-slot:title>Grafik Pendapatan</x-slot:title>
            <x-slot:menu>
                <x-select label="Pilih Kategori Pendapatan" wire:model.live="selectedKategoriPendapatan"
                    :options="collect($kategoriPendapatanList)
                        ->map(fn($k) => ['id' => $k['id'], 'name' => $k['name']])
                        ->prepend(['id' => null, 'name' => 'Semua Pendapatan'])" option-label="name" option-value="id" class="w-full md:w-64" />
            </x-slot:menu>
            <div class="w-full min-w-[320px]">
                <x-chart wire:model="pendapatanChart" />
            </div>
        </x-card>

        <x-card class="col-span-10 overflow-x-auto">
            <x-slot:title>Grafik Pengeluaran</x-slot:title>
            <x-slot:menu>
                <x-select label="Pilih Kategori Pengeluaran" wire:model.live="selectedKategoriPengeluaran"
                    :options="collect($kategoriPengeluaranList)
                        ->map(fn($k) => ['id' => $k['id'], 'name' => $k['name']])
                        ->prepend(['id' => null, 'name' => 'Semua Pengeluaran'])" option-label="name" option-value="id" class="w-full md:w-64" />
            </x-slot:menu>
            <div class="w-full min-w-[320px]">
                <x-chart wire:model="pengeluaranChart" />
            </div>
        </x-card>

        <!-- Stok Charts -->
        <x-card class="col-span-10 md:col-span-5 overflow-x-auto">
            <x-slot:title>Stok Telur</x-slot:title>
            <div class="w-full min-w-[320px]">
                <x-chart wire:model="stokTelurChart" />
            </div>
        </x-card>

        <x-card class="col-span-10 md:col-span-5 overflow-x-auto">
            <x-slot:title>Stok Tray</x-slot:title>
            <div class="w-full min-w-[320px]">
                <x-chart wire:model="stokTrayChart" />
            </div>
        </x-card>

        <x-card class="col-span-10 overflow-x-auto">
            <x-slot:title>Stok Obat</x-slot:title>
            <div class="w-full min-w-[320px]">
                <x-chart wire:model="stokObatChart" />
            </div>
        </x-card>

        <x-card class="col-span-10 overflow-x-auto">
            <x-slot:title>Stok Sentrat</x-slot:title>
            <div class="w-full min-w-[320px]">
                <x-chart wire:model="stokSentratChart" />
            </div>
        </x-card>
    </div>
</div>
