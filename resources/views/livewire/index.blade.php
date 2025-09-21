<?php

namespace App\Livewire;

use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\User;
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
    public array $myChart = [];
    public array $stokChart = [];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->setDefaultDates();
        $this->chartGross();
        $this->chartStokBarang();
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
        $this->chartGross();
        $this->chartStokBarang();
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

        $this->chartGross();
        $this->chartStokBarang();
        $this->toast('Periode tanggal berhasil diperbarui', 'success');
    }

    public function chartGross()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // Ambil data transaksi
        $transactions = Transaksi::with('kategori')
            ->whereBetween('tanggal', [$start, $end])
            ->orderBy('tanggal')
            ->get();

        // Kelompokkan per tanggal
        $grouped = $transactions->groupBy(fn($trx) => Carbon::parse($trx->tanggal)->format('Y-m-d'));

        // Buat array tanggal unik (agar tanggal tetap urut meski kosong)
        $dates = collect();
        $period = \Carbon\CarbonPeriod::create($start, $end);
        foreach ($period as $date) {
            $dates->push($date->format('Y-m-d'));
        }

        // Siapkan data untuk income & expense
        $incomeData = [];
        $expenseData = [];

        foreach ($dates as $date) {
            $dayTransactions = $grouped->get($date, collect());
            $income = $dayTransactions->filter(fn($trx) => $trx->kategori?->type === 'Pendapatan')->sum('total');
            $expense = $dayTransactions->filter(fn($trx) => $trx->kategori?->type === 'Pengeluaran')->sum('total');

            $incomeData[] = $income;
            $expenseData[] = $expense;
        }

        // Buat chart
        $this->myChart = [
            'type' => 'line',
            'data' => [
                'labels' => $dates->toArray(),
                'datasets' => [
                    [
                        'label' => 'Pendapatan',
                        'data' => $incomeData,
                        'borderColor' => '#4CAF50',
                        'backgroundColor' => 'rgba(76, 175, 80, 0.2)',
                        'fill' => true,
                        'tension' => 0.3,
                    ],
                    [
                        'label' => 'Pengeluaran',
                        'data' => $expenseData,
                        'borderColor' => '#F44336',
                        'backgroundColor' => 'rgba(244, 67, 54, 0.2)',
                        'fill' => true,
                        'tension' => 0.3,
                    ],
                ],
            ],
        ];
    }

    public function chartStokBarang()
    {
        $barangs = Barang::select('id', 'name', 'stok')->where('stok', '>', 0)->get();

        $grouped = $barangs->groupBy(fn($b) => $b->name);
        $data = $grouped->map(fn($items) => $items->sum('stok'))->toArray();

        $colors = collect($data)->map(fn() => sprintf('#%06X', mt_rand(0, 0xffffff)))->values()->toArray();

        $this->stokChart = [
            'type' => 'bar', // ✅ Livewire aman dengan string biasa
            'data' => [
                'labels' => array_values(array_keys($data)), // pastikan hanya array numerik
                'datasets' => [
                    [
                        'label' => 'Stok Barang',
                        'data' => array_values($data),
                        'backgroundColor' => $colors,
                        'borderWidth' => 1,
                    ],
                ],
            ],
            'options' => [
                'indexAxis' => 'x',
                'responsive' => true,
                'plugins' => [
                    'legend' => ['display' => false],
                    'tooltip' => [
                        'callbacks' => [
                            // ❌ jangan pakai closure di sini, Livewire tidak bisa serialize closure
                            // Kita kirim string config biasa, biarkan Chart.js yang handle
                        ],
                    ],
                ],
                'scales' => [
                    'x' => ['beginAtZero' => true],
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
        return Transaksi::whereHas('kategori', fn($q) => $q->where('type', 'Aset'))
            ->whereBetween('tanggal', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
            ->sum('total');
    }

    public function liabiliatsTotal(): int
    {
        return Transaksi::whereHas('kategori', fn($q) => $q->where('type', 'Aset'))
            ->whereBetween('tanggal', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
            ->sum('total');
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

<div class="">
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
                    [
                        'id' => 'month',
                        'name' => 'Bulan Ini',
                        'hint' => 'Data bulan berjalan',
                        'icon' => 'o-chart-pie',
                    ],
                    [
                        'id' => 'year',
                        'name' => 'Tahun Ini',
                        'hint' => 'Data tahun berjalan',
                        'icon' => 'o-chart-bar',
                    ],
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
                    option-description="hint" class="gap-4">
                </x-select>

                @if ($period === 'custom')
                    <div class="flex flex-col gap-4 mt-2">
                        <form wire:submit.prevent="applyDateRange">
                            <div class="flex flex-col md:flex-row gap-4 items-start md:items-end">
                                <x-input type="date" label="Dari Tanggal" wire:model="startDate" :max="now()->format('Y-m-d')"
                                    class="w-full md:w-auto" />

                                <x-input type="date" label="Sampai Tanggal" wire:model="endDate" :min="$startDate"
                                    :max="now()->format('Y-m-d')" class="w-full md:w-auto" />

                                <x-button spinner label="Terapkan" type="submit" icon="o-check"
                                    class="btn-primary mt-2 md:mt-6 w-full md:w-auto" />
                            </div>

                            @error('endDate')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror

                            <div class="text-sm text-gray-500 mt-2">
                                Periode terpilih:
                                {{ $startDate->translatedFormat('d M Y') }} -
                                {{ $endDate->translatedFormat('d M Y') }}
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </x-slot:actions>
    </x-header>

    <!-- Grid Container -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Gross -->
        <x-card class=" rounded-lg shadow p-4">
            <div class="flex items-center justify-center gap-3">
                <x-icon name="fas.money-bill-wave" class="text-purple-500 w-10 h-10" />
                <div>
                    <p class="">Pendapatan</p>
                    <p class="text-xl  font-bold">Rp. {{ number_format($incomeTotal) }}</p>
                </div>
            </div>
        </x-card>

        <!-- Orders -->
        <x-card class=" rounded-lg shadow p-4">
            <div class="flex items-center justify-center gap-3">
                <x-icon name="o-shopping-bag" class="text-blue-500 w-10 h-10" />
                <div>
                    <p class="">Pengeluaran</p>
                    <p class="text-xl  font-bold">Rp. {{ number_format($expenseTotal) }}</p>
                </div>
            </div>
        </x-card>

        <!-- New Customers -->
        <x-card class=" rounded-lg shadow p-4">
            <div class="flex items-center justify-center gap-3">
                <x-icon name="o-user-plus" class="text-green-500 w-10 h-10" />
                <div>
                    <p class="">Aset</p>
                    <p class="text-xl  font-bold">Rp. {{ number_format($assetTotal) }}</p>
                </div>
            </div>
        </x-card>

        <!-- Built with -->
        <x-card class=" rounded-lg shadow p-4">
            <div class="flex items-center justify-center gap-3">
                <x-icon name="o-gift" class="text-yellow-500 w-10 h-10" />
                <div>
                    <p class="">Liabilitas</p>
                    <p class="text-xl  font-bold">Rp. {{ number_format($liabiliatsTotal) }}</p>
                </div>
            </div>
        </x-card>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mt-4">
        <x-card class="grid col-span-3">
            <x-slot:title>Pendapatan dan Pengeluaran</x-slot:title>
            <x-chart wire:model="myChart" />
        </x-card>

        <x-card class="grid col-span-3">
            <x-slot:title>Stok Barang</x-slot:title>
            <x-chart wire:model="stokChart" />
        </x-card>
    </div>
</div>
