<?php

namespace App\Livewire;

use App\Models\Transaksi;
use App\Models\Kategori;
use Livewire\Volt\Component;
use App\Exports\LabaRugiExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

new class extends Component {
    public $startDate;
    public $endDate;

    public $pendapatanData = [];
    public $pengeluaranData = [];
    public $expanded = []; // toggle detail
    public $bebanPajak = 0;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->generateReport();
    }

    public function updated($field)
    {
        if (in_array($field, ['startDate', 'endDate'])) {
            $this->generateReport();
        }
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new LabaRugiExport($this->startDate, $this->endDate), 'laba_rugi.xlsx');
    }

    public function generateReport()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // Semua kategori
        $kategoriPendapatan = Kategori::where('type', 'Pendapatan')->pluck('name')->toArray();
        $kategoriPengeluaran = Kategori::where('type', 'Pengeluaran')->pluck('name')->toArray();

        // Mapping kelompok
        $mappingPendapatan = [
            'Pendapatan Telur' => ['Penjualan Telur Horn', 'Penjualan Telur Bebek', 'Penjualan Telur Puyuh', 'Penjualan Telur Arap'],
            'Pendapatan Pakan' => ['Penjualan Pakan Sentrat/Pabrikan', 'Penjualan Pakan Kucing', 'Penjualan Pakan Curah'],
            'Pendapatan Obat' => ['Penjualan Obat-Obatan'],
            'Pendapatan Eggtray' => ['Penjualan EggTray'],
            'Pendapatan Perlengkapan' => ['Penjualan Triplex', 'Penjualan Terpal', 'Penjualan Ban Bekas', 'Penjualan Sak Campur', 'Penjualan Tali'],
            'Pendapatan Non Penjualan' => ['Pemasukan Dapur', 'Pemasukan Transport Setoran', 'Pemasukan Transport Pedagang'],
            'Pendapatan Lain-Lain' => ['Penjualan Lain-Lain'],
        ];

        $mappingPengeluaran = [
            'Beban Transport' => ['Beban Transport', 'Beban BBM'],
            'Beban Operasional' => ['Beban Kantor', 'Beban Gaji', 'Beban Konsumsi', 'Peralatan', 'Perlengkapan', 'Beban Servis', 'Beban TAL'],
            'Beban Produksi' => ['Beban Telur Bentes', 'Beban Telur Ceplok', 'Beban Telur Prok', 'Beban Barang Kadaluarsa', 'HPP'],
            'Beban Bunga & Pajak' => ['Beban Bunga', 'Beban Pajak'],
            'Beban Sedekah' => ['ZIS'],
        ];

        // --- Pendapatan per kategori ---
        $pendapatanFlat = Transaksi::with('details.kategori')
            ->whereHas('details.kategori', fn($q) => $q->where('type', 'Pendapatan'))
            ->whereBetween('tanggal', [$start, $end])
            ->get()
            ->flatMap(fn($trx) => $trx->details)
            ->filter(fn($d) => $d->kategori && $d->kategori->type == 'Pendapatan')
            ->groupBy(fn($d) => $d->kategori->name)
            ->map(fn($group) => 
                $group->filter(fn($d)=>strtolower($d->transaksi->type ?? '')==='kredit')->sum('sub_total') -
                $group->filter(fn($d)=>strtolower($d->transaksi->type ?? '')==='debit')->sum('sub_total')
            )
            ->toArray();

        // --- Pengeluaran per kategori ---
        $pengeluaranFlat = Transaksi::with('details.kategori')
            ->whereHas('details.kategori', fn($q) => $q->where('type', 'Pengeluaran'))
            ->whereBetween('tanggal', [$start, $end])
            ->get()
            ->flatMap(fn($trx) => $trx->details)
            ->filter(fn($d) => $d->kategori && $d->kategori->type == 'Pengeluaran')
            ->groupBy(fn($d) => $d->kategori->name)
            ->map(fn($group) => 
                $group->filter(fn($d)=>strtolower($d->transaksi->type ?? '')==='debit')->sum('sub_total') -
                $group->filter(fn($d)=>strtolower($d->transaksi->type ?? '')==='kredit')->sum('sub_total')
            )
            ->toArray();

        // Beban Pajak
        $this->bebanPajak = $pengeluaranFlat['Beban Pajak'] ?? 0;

        // --- Kelompokkan pendapatan ---
        $this->pendapatanData = [];
        foreach ($mappingPendapatan as $kelompok => $subs) {
            $detail = [];
            $total = 0;
            foreach ($subs as $sub) {
                $nilai = $pendapatanFlat[$sub] ?? 0;
                $detail[$sub] = $nilai;
                $total += $nilai;
            }
            $this->pendapatanData[$kelompok] = ['total' => $total, 'detail' => $detail];
        }

        // --- Kelompokkan pengeluaran ---
        $this->pengeluaranData = [];
        foreach ($mappingPengeluaran as $kelompok => $subs) {
            $detail = [];
            $total = 0;
            foreach ($subs as $sub) {
                $nilai = $pengeluaranFlat[$sub] ?? 0;
                $detail[$sub] = $nilai;
                $total += $nilai;
            }
            $this->pengeluaranData[$kelompok] = ['total' => $total, 'detail' => $detail];
        }
    }

    public function with()
    {
        $totalPendapatan = array_sum(array_map(fn($d) => $d['total'], $this->pendapatanData));
        $totalPengeluaran = array_sum(array_map(fn($d) => $d['total'], $this->pengeluaranData));
        $labaSebelumPajak = $totalPendapatan - $totalPengeluaran;
        $labaSetelahPajak = $labaSebelumPajak - $this->bebanPajak;

        return [
            'pendapatanData' => $this->pendapatanData,
            'pengeluaranData' => $this->pengeluaranData,
            'totalPendapatan' => $totalPendapatan,
            'totalPengeluaran' => $totalPengeluaran,
            'labaSebelumPajak' => $labaSebelumPajak,
            'bebanPajak' => $this->bebanPajak,
            'labaSetelahPajak' => $labaSetelahPajak,
        ];
    }
};
?>
<div class="p-6 space-y-6">
    <x-header title="Laporan Laba Rugi" separator>
        <x-slot:actions>
            <x-button wire:click="export" icon="fas.download" primary>Export Excel</x-button>
            <div class="flex grid grid-cols-1 md:grid-cols-2 items-end">
                <x-input type="date" label="Dari Tanggal" wire:model.live="startDate" />
                <x-input type="date" label="Sampai Tanggal" wire:model.live="endDate" />
            </div>
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-card>
            <h3 class="text-lg font-semibold text-green-800">
                <i class="fas fa-coins text-green-600"></i>Total Pendapatan
            </h3>
            <p class="text-2xl font-bold text-green-700 mt-2">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</p>
        </x-card>

        <x-card>
            <h3 class="text-lg font-semibold text-red-800">
                <i class="fas fa-wallet text-red-600"></i>Total Pengeluaran
            </h3>
            <p class="text-2xl font-bold text-red-700 mt-2">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</p>
        </x-card>

        <x-card>
            <h3 class="text-lg font-semibold">
                <i class="fas fa-chart-line text-blue-600"></i>Laba Sebelum Pajak
            </h3>
            <p class="text-2xl font-bold {{ $labaSebelumPajak >=0 ? 'text-green-700':'text-red-700' }} mt-2">
                Rp {{ number_format($labaSebelumPajak,0,',','.') }}
            </p>
        </x-card>

        <x-card>
            <h3 class="text-lg font-semibold">
                <i class="fas fa-calculator text-purple-600"></i>Laba Setelah Pajak
            </h3>
            <p class="text-2xl font-bold {{ $labaSetelahPajak >=0 ? 'text-green-700':'text-red-700' }} mt-2">
                Rp {{ number_format($labaSetelahPajak,0,',','.') }}
            </p>
            <p class="text-sm text-gray-500 mt-1">(Beban Pajak: Rp {{ number_format($bebanPajak,0,',','.') }})</p>
        </x-card>
    </div>

    <x-card class="mt-4">
        <h3 class="text-xl font-semibold mb-4"><i class="fas fa-list-ul"></i>Rincian</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Pendapatan -->
            <div>
                <h4 class="text-lg font-semibold text-green-700 mb-2"><i class="fas fa-arrow-up"></i>Pendapatan per Kelompok</h4>
                <ul class="divide-y divide-gray-200">
                    @foreach($pendapatanData as $kelompok => $data)
                        <li class="py-2">
                            <div class="flex justify-between cursor-pointer" wire:click="$toggle('expanded.{{ $kelompok }}')">
                                <span class="font-medium">{{ $kelompok }}</span>
                                <span class="text-green-700">Rp {{ number_format($data['total'],0,',','.') }}</span>
                            </div>
                            @if($expanded[$kelompok] ?? false)
                                <ul class="pl-4 mt-2">
                                    @foreach($data['detail'] as $sub => $val)
                                        <li class="flex justify-between py-1 text-green-600">
                                            <span>{{ $sub }}</span>
                                            <span>Rp {{ number_format($val,0,',','.') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Pengeluaran -->
            <div>
                <h4 class="text-lg font-semibold text-red-700 mb-2"><i class="fas fa-arrow-down"></i>Pengeluaran per Kelompok</h4>
                <ul class="divide-y divide-gray-200">
                    @foreach($pengeluaranData as $kelompok => $data)
                        <li class="py-2">
                            <div class="flex justify-between cursor-pointer" wire:click="$toggle('expanded.{{ $kelompok }}')">
                                <span class="font-medium">{{ $kelompok }}</span>
                                <span class="text-red-700">Rp {{ number_format($data['total'],0,',','.') }}</span>
                            </div>
                            @if($expanded[$kelompok] ?? false)
                                <ul class="pl-4 mt-2">
                                    @foreach($data['detail'] as $sub => $val)
                                        <li class="flex justify-between py-1 text-red-600">
                                            <span>{{ $sub }}</span>
                                            <span>Rp {{ number_format($val,0,',','.') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>
    </x-card>
</div>
