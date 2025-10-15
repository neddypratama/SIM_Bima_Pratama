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
    public string $startDate;
    public string $endDate;

    public array $pendapatanData = [];
    public array $pengeluaranData = [];

    public float $bebanPajak = 0;

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

        $kategoriPendapatan = Kategori::where('type', 'Pendapatan')->pluck('name');
        $kategoriPengeluaran = Kategori::where('type', 'Pengeluaran')->pluck('name');

        // == Pendapatan ==
        $pendapatan = Transaksi::with('details.kategori')
            ->whereHas('details.kategori', fn($q) => $q->where('type', 'Pendapatan'))
            ->whereBetween('tanggal', [$start, $end])
            ->get()
            ->flatMap(fn($trx) => $trx->details)
            ->filter(fn($detail) => $detail->kategori && $detail->kategori->type == 'Pendapatan')
            ->groupBy(fn($detail) => $detail->kategori->name)
            ->map(function ($group) {
                $kredit = $group->filter(fn($d) => strtolower($d->transaksi->type ?? '') === 'kredit')->sum('sub_total');
                $debit = $group->filter(fn($d) => strtolower($d->transaksi->type ?? '') === 'debit')->sum('sub_total');
                return $kredit - $debit;
            });

        // == Pengeluaran ==
        $pengeluaran = Transaksi::with('details.kategori')
            ->whereHas('details.kategori', fn($q) => $q->where('type', 'Pengeluaran'))
            ->whereBetween('tanggal', [$start, $end])
            ->get()
            ->flatMap(fn($trx) => $trx->details)
            ->filter(fn($detail) => $detail->kategori && $detail->kategori->type == 'Pengeluaran')
            ->groupBy(fn($detail) => $detail->kategori->name)
            ->map(function ($group) {
                $debit = $group->filter(fn($d) => strtolower($d->transaksi->type ?? '') === 'debit')->sum('sub_total');
                $kredit = $group->filter(fn($d) => strtolower($d->transaksi->type ?? '') === 'kredit')->sum('sub_total');
                return $debit - $kredit;
            });

        // == Beban Pajak ==
        $this->bebanPajak = Transaksi::with('details.kategori')
            ->whereHas('details.kategori', fn($q) => $q->where('type', 'Pengeluaran')->where('name', 'Beban Pajak'))
            ->whereBetween('tanggal', [$start, $end])
            ->get()
            ->flatMap(fn($trx) => $trx->details)
            ->filter(fn($detail) => $detail->kategori && $detail->kategori->type == 'Pengeluaran' && $detail->kategori->name == 'Beban Pajak')
            ->sum('sub_total');

        // == Pastikan semua kategori tetap muncul ==
        $this->pendapatanData = $kategoriPendapatan
            ->mapWithKeys(
                fn($name) => [
                    $name => $pendapatan[$name] ?? 0,
                ],
            )
            ->toArray();

        $this->pengeluaranData = $kategoriPengeluaran
            ->mapWithKeys(
                fn($name) => [
                    $name => $pengeluaran[$name] ?? 0,
                ],
            )
            ->toArray();

    }

    public function with()
    {
        $totalPendapatan = array_sum($this->pendapatanData);
        $totalPengeluaran = array_sum($this->pengeluaranData);

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
                <i class="fas fa-coins text-green-600 mr-2"></i>Total Pendapatan
            </h3>
            <p class="text-2xl font-bold text-green-700 mt-2">
                Rp {{ number_format($totalPendapatan, 0, ',', '.') }}
            </p>
        </x-card>

        <x-card>
            <h3 class="text-lg font-semibold text-red-800">
                <i class="fas fa-wallet text-red-600 mr-2"></i>Total Pengeluaran
            </h3>
            <p class="text-2xl font-bold text-red-700 mt-2">
                Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}
            </p>
        </x-card>

        <x-card>
            <h3 class="text-lg font-semibold">
                <i class="fas fa-chart-line text-blue-600 mr-2"></i>Laba Sebelum Pajak
            </h3>
            <p class="text-2xl font-bold {{ $labaSebelumPajak >= 0 ? 'text-green-700' : 'text-red-700' }} mt-2">
                Rp {{ number_format($labaSebelumPajak, 0, ',', '.') }}
            </p>
        </x-card>

        <x-card>
            <h3 class="text-lg font-semibold">
                <i class="fas fa-calculator text-purple-600 mr-2"></i>Laba Setelah Pajak
            </h3>
            <p class="text-2xl font-bold {{ $labaSetelahPajak >= 0 ? 'text-green-700' : 'text-red-700' }} mt-2">
                Rp {{ number_format($labaSetelahPajak, 0, ',', '.') }}
            </p>
            <p class="text-sm text-gray-500 mt-1">
                (Beban Pajak: Rp {{ number_format($bebanPajak, 0, ',', '.') }})
            </p>
        </x-card>
    </div>

    <x-card class="mt-4">
        <h3 class="text-xl font-semibold mb-4"><i class="fas fa-list-ul mr-2"></i>Rincian</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="text-lg font-semibold text-green-700 mb-2"><i class="fas fa-arrow-up mr-2"></i>Pendapatan per
                    Kategori</h4>
                <ul class="divide-y divide-gray-200">
                    @foreach ($pendapatanData as $kategori => $total)
                        <li class="flex justify-between py-2">
                            <span class="font-medium">{{ $kategori }}</span>
                            <span class="text-green-700">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h4 class="text-lg font-semibold text-red-700 mb-2"><i class="fas fa-arrow-down mr-2"></i>Pengeluaran
                    per Kategori</h4>
                <ul class="divide-y divide-gray-200">
                    @foreach ($pengeluaranData as $kategori => $total)
                        <li class="flex justify-between py-2">
                            <span class="font-medium">{{ $kategori }}</span>
                            <span class="text-red-700">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </x-card>
</div>
