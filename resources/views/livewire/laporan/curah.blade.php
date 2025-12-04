<?php

namespace App\Livewire;

use App\Models\Transaksi;
use App\Models\Barang;
use Livewire\Volt\Component;
use App\Exports\LabaRugiExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $startDate;
    public $endDate;

    public $stokCurah = 0;
    public $pendapatanData = [];
    public $pengeluaranData = [];
    public $expanded = [];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->generateReport();
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new LabaRugiExport($this->startDate, $this->endDate), 'laba_rugi_curah.xlsx');
    }

    public function updatedStartDate()
    {
        $this->generateReport();
    }
    public function updatedEndDate()
    {
        $this->generateReport();
    }

    public function generateReport()
    {
        $first = Transaksi::orderBy('tanggal')->first();
        $last = Transaksi::orderBy('tanggal', 'desc')->first();

        if (!$first || !$last) {
            $this->pendapatanData = [];
            $this->pengeluaranData = [];
            return;
        }

        // ✅ Fix pribadi: Pakai tanggal dari tabel transaksi
        $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : Carbon::parse($first->tanggal)->startOfDay();

        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : Carbon::parse($last->tanggal)->endOfDay();

        $stokPakanCurah = Transaksi::with('details.kategori', 'details.barang.jenis')
            ->whereHas('details.kategori', fn($q) => $q->where('type', 'Aset')->where('name', 'Stok Pakan'))
            ->whereHas('details.barang.jenis', fn($q) => $q->where('name', 'Pakan Curah'))
            ->whereBetween('tanggal', [Carbon::parse('2025-10-31')->startOfDay(), $end])
            ->get()
            ->flatMap(fn($trx) => $trx->details)
            ->filter(fn($d) => $d->kategori && ($d->barang->jenis->name ?? '') === 'Pakan Curah')
            ->groupBy(fn($d) => $d->kategori->name)
            ->map(fn($group) => $group->where(fn($i) => strtolower($i->transaksi->type ?? '') === 'debit')->sum('sub_total') - $group->where(fn($i) => strtolower($i->transaksi->type ?? '') === 'kredit')->sum('sub_total'))
            ->toArray();
        
        $this->stokCurah = $stokPakanCurah['Stok Pakan'];

        // ✅ Ambil semua nama barang curah dari master dan jadikan acuan urutan
        $barangCurahMaster = Barang::whereHas('jenis', fn($q) => $q->where('name', 'Pakan Curah'))->pluck('name')->toArray();

        // ✅ Pendapatan sudah bersih (Kredit - Debit) per barang
        $pendapatanFlat = Transaksi::with('details.kategori', 'details.barang')
            ->whereHas('details.kategori', fn($q) => $q->where('type', 'Pendapatan')->where('name', 'Penjualan Pakan Curah'))
            ->whereBetween('tanggal', [$start, $end])
            ->get()
            ->flatMap(fn($trx) => $trx->details)
            ->groupBy(fn($d) => $d->barang->name)
            ->map(fn($group) => $group->filter(fn($d) => strtolower($d->transaksi->type ?? '') === 'kredit')->sum('sub_total') - $group->filter(fn($d) => strtolower($d->transaksi->type ?? '') === 'debit')->sum('sub_total'))
            ->toArray();

        // ✅ Urutkan detail pendapatan berdasarkan master barang
        $pendapatanDetailFinal = [];
        $totalPendapatan = 0;

        foreach ($barangCurahMaster as $barang) {
            $nilai = $pendapatanFlat[$barang] ?? 0;
            $pendapatanDetailFinal[$barang] = $nilai;
            $totalPendapatan += $nilai;
        }

        $this->pendapatanData = [
            'Penjualan Pakan Curah' => [
                'total' => $totalPendapatan,
                'detail' => $pendapatanDetailFinal,
            ],
            '_range' => [
                'start' => $start->toDateTimeString(),
                'end' => $end->toDateTimeString(),
            ],
        ];

        // ✅ Query HPP Kredit & Debit
        $hppDB = DB::table('detail_transaksis as td')
            ->join('kategoris as k', 'k.id', '=', 'td.kategori_id')
            ->join('transaksis as t', 't.id', '=', 'td.transaksi_id')
            ->join('barangs as b', 'b.id', '=', 'td.barang_id')
            ->join('jenis_barangs as jb', 'jb.id', '=', 'b.jenis_id')
            ->where('k.name', 'HPP')
            ->where('jb.name', 'Pakan Curah')
            ->whereBetween('t.tanggal', [$start, $end])
            ->whereIn('b.name', $barangCurahMaster)
            ->select('b.name as barang_name', DB::raw("SUM(CASE WHEN LOWER(t.type) = 'kredit' THEN td.sub_total ELSE 0 END) as total_kredit"), DB::raw("SUM(CASE WHEN LOWER(t.type) = 'debit'  THEN td.sub_total ELSE 0 END) as total_debit"))
            ->groupBy('b.name')
            ->get()
            ->keyBy('barang_name');

        // ✅ Format HPP (nilai dan urutan sama)
        $hppDetailFinal = [];
        $totalHPP = 0;

        foreach ($barangCurahMaster as $barang) {
            $k = $hppDB[$barang]->total_kredit ?? 0;
            $d = $hppDB[$barang]->total_debit ?? 0;
            $nilai = $d - $k;

            $hppDetailFinal[$barang] = $nilai;
            $totalHPP += $nilai;
        }

        $this->pengeluaranData = [
            'HPP Pakan Curah' => [
                'total' => $totalHPP,
                'detail' => $hppDetailFinal,
            ],
            '_range' => [
                'start' => $start->toDateTimeString(),
                'end' => $end->toDateTimeString(),
            ],
        ];
    }

    public function with()
    {
        $pd = $this->pendapatanData['Penjualan Pakan Curah']['total'] ?? 0;
        $pp = $this->pengeluaranData['HPP Pakan Curah']['total'] ?? 0;

        return [
            'pendapatanData' => $this->pendapatanData,
            'pengeluaranData' => $this->pengeluaranData,
            'totalPendapatan' => $pd,
            'totalPengeluaran' => $pp,
            'labaBersih' => $pd - $pp,
            'stokCurah' => $this->stokCurah,
        ];
    }
};
?>

<div class="p-6 space-y-6">

    <x-header title="Laporan Laba Rugi - Pakan Curah" separator>
        <x-slot:actions>

            <x-button wire:click="export" icon="fas.download" primary>Export Excel</x-button>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end">

                <x-input type="date" label="Dari Tanggal" wire:model.live="startDate" />
                <x-input type="date" label="Sampai Tanggal" wire:model.live="endDate" />

            </div>
        </x-slot:actions>
    </x-header>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-card>
            <h3 class="text-lg font-semibold text-blue-700"><i class="fas fa-arrow-up"></i> Stok Pakan Curah</h3>
            <p class="text-2xl font-bold text-blue-600 mt-2">Rp {{ number_format($stokCurah, 0, ',', '.') }}</p>
        </x-card>
        <x-card>
            <h3 class="text-lg font-semibold text-green-700"><i class="fas fa-arrow-up"></i> Total Pendapatan</h3>
            <p class="text-2xl font-bold text-green-600 mt-2">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</p>
        </x-card>

        <x-card>
            <h3 class="text-lg font-semibold text-red-700"><i class="fas fa-arrow-down"></i> Total Pengeluaran (HPP)
            </h3>
            <p class="text-2xl font-bold text-red-600 mt-2">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</p>
        </x-card>

        <x-card>
            <h3 class="text-lg font-semibold {{ $labaBersih >= 0 ? 'text-green-700' : 'text-red-700' }}"><i
                    class="fas fa-chart-line"></i> Laba Bersih</h3>
            <p class="text-2xl font-bold {{ $labaBersih >= 0 ? 'text-green-700' : 'text-red-700' }} mt-2">
                Rp {{ number_format($labaBersih, 0, ',', '.') }}
            </p>
        </x-card>
    </div>

    <!-- Detail 2 COL -->
    <x-card>
        <div class="grid grid-cols-2 gap-6">

            <!-- Pendapatan -->
            @php $pd = $pendapatanData['Penjualan Pakan Curah']; @endphp
            <div>
                <div class="flex justify-between cursor-pointer border-b pb-2"
                    wire:click="$toggle('expanded.PenjualanCurah')">
                    <span class="text-lg font-medium text-green-700">Penjualan Pakan Curah</span>
                    <span class="text-green-600 font-bold text-lg">Rp
                        {{ number_format($pd['total'], 0, ',', '.') }}</span>
                </div>

                @if ($expanded['PenjualanCurah'] ?? false)
                    <ul class="mt-3">
                        @foreach ($pd['detail'] as $barang => $nilai)
                            <li class="flex justify-between py-1">
                                <span>{{ $barang }}</span>
                                <span class="text-green-600">Rp {{ number_format($nilai, 0, ',', '.') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- HPP -->
            @php $pp = $pengeluaranData['HPP Pakan Curah']; @endphp
            <div>
                <div class="flex justify-between cursor-pointer border-b pb-2"
                    wire:click="$toggle('expanded.HPPCurah')">
                    <span class="text-lg font-medium text-red-700">HPP Pakan Curah</span>
                    <span class="text-red-600 font-bold text-lg">Rp
                        {{ number_format($pp['total'], 0, ',', '.') }}</span>
                </div>

                @if ($expanded['HPPCurah'] ?? false)
                    <ul class="mt-3">
                        @foreach ($pp['detail'] as $barang => $nilai)
                            <li class="flex justify-between py-1">
                                <span>{{ $barang }}</span>
                                <span class="text-red-600">Rp {{ number_format($nilai, 0, ',', '.') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>
    </x-card>

</div>
