<?php

namespace App\Livewire;

use App\Models\Transaksi;
use Livewire\Volt\Component;
use App\Exports\LabaRugiExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $startDate;
    public $endDate;

    public $pendapatanData = [];
    public $pengeluaranData = [];
    public $expanded = [];

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
        $this->pendapatanData = [];
        $this->pengeluaranData = [];
        $this->expanded = [];

        $first = Transaksi::orderBy('tanggal')->first();
        $last = Transaksi::orderByDesc('tanggal')->first();

        if (!$first || !$last) {
            return;
        }

        $start = Carbon::parse($this->startDate ?? $first->tanggal)->startOfDay();
        $end = Carbon::parse($this->endDate ?? $last->tanggal)->endOfDay();

        /* =====================================================
            1. LAPORAN PENDAPATAN & PENGELUARAN (NON HPP)
        ===================================================== */

        $laporans = DB::table('detail_kategoris as dk')->leftJoin('kategoris as k', 'k.detail_kategori_id', '=', 'dk.id')->where('k.name', 'not like', '% Pakan Curah')->select('dk.name as laporan', 'dk.type', 'k.name as kategori')->orderBy('dk.id')->get();

        /* =====================================================
            3. HPP PALING ATAS (GROUPING)
        ===================================================== */

        // Mapping kelompok HPP utama
        $hppKelompok = [
            'HPP Telur' => ['HPP Telur Horn', 'HPP Telur Bebek', 'HPP Telur Puyuh', 'HPP Telur Arab', 'HPP Telur Asin'],
            'HPP Pakan' => ['HPP Pakan Sentrat/Pabrikan', 'HPP Pakan Kucing'],
            'HPP Obat' => ['HPP Obat-Obatan'],
            'HPP Eggtray' => ['HPP Tray'],
        ];

        // Ambil hasil total HPP per jenis barang langsung dari DB
        $hppResults = DB::table('detail_transaksis as td')
            ->join('kategoris as k', 'k.id', '=', 'td.kategori_id')
            ->join('barangs as b', 'b.id', '=', 'td.barang_id')
            ->join('jenis_barangs as jb', 'jb.id', '=', 'b.jenis_id')
            ->join('transaksis as t', 't.id', '=', 'td.transaksi_id')
            ->select(DB::raw("CONCAT('HPP ', jb.name) AS hpp_name"), 'jb.name as jenis_name', DB::raw('SUM(td.sub_total) AS total_hpp'))
            ->where('k.name', 'HPP')
            ->where('t.status', 'Selesai')
            ->whereBetween('t.tanggal', [$start, $end])
            ->groupBy('jb.name')
            ->orderBy('jb.name')
            ->get()
            ->keyBy('hpp_name'); // supaya mudah diakses per nama HPP

        // Siapkan struktur pengeluaranFlat sesuai kelompok
        foreach ($hppKelompok as $kelompok => $jenisList) {
            $detail = [];
            $total = 0;

            foreach ($jenisList as $jenis) {
                $hppName = $jenis; // ← perbaikan di sini
                $nilai = $hppResults[$hppName]->total_hpp ?? 0;
                $detail[$hppName] = $nilai;
                $total += $nilai;
            }

            // Simpan dalam pengeluaranFlat (mengikuti format laporan laba rugi)
            $this->pengeluaranData[$kelompok] = [
                'total' => $total,
                'detail' => $detail,
            ];
        }

        foreach ($laporans as $row) {
            if ($row->type === 'Pendapatan') {
                $this->pendapatanData[$row->laporan]['detail'][$row->kategori] = 0;
                $this->pendapatanData[$row->laporan]['total'] ??= 0;
            }

            if ($row->type === 'Pengeluaran' && !str_starts_with($row->kategori ?? '', 'HPP')) {
                $this->pengeluaranData[$row->laporan]['detail'][$row->kategori] = 0;
                $this->pengeluaranData[$row->laporan]['total'] ??= 0;
            }
        }

        // dd($laporans);

        /* =====================================================
            2. ISI DATA TRANSAKSI
        ===================================================== */

        $rows = DB::table('detail_transaksis as dt')
            ->join('kategoris as k', 'k.id', '=', 'dt.kategori_id')
            ->join('detail_kategoris as dk', 'dk.id', '=', 'k.detail_kategori_id')
            ->join('transaksis as t', 't.id', '=', 'dt.transaksi_id')
            ->whereBetween('t.tanggal', [$start, $end])
            ->where('k.name', 'not like', '% Pakan Curah')
            ->where('t.status', 'Selesai')
            ->select(
                'dk.name as laporan',
                'dk.type',
                'k.name as kategori',
                DB::raw("
            SUM(
                CASE
                    WHEN dk.type = 'Pendapatan' THEN
                        CASE
                            WHEN LOWER(t.type) = 'kredit' THEN dt.sub_total
                            WHEN LOWER(t.type) = 'debit' THEN -dt.sub_total
                            ELSE 0
                        END

                    WHEN dk.type = 'Pengeluaran' THEN
                        CASE
                            WHEN LOWER(t.type) = 'debit' THEN dt.sub_total
                            WHEN LOWER(t.type) = 'kredit' THEN -dt.sub_total
                            ELSE 0
                        END

                    ELSE 0
                END
            ) as total
        "),
            )
            ->groupBy('dk.name', 'dk.type', 'k.name')
            ->get();

        foreach ($rows as $row) {
            if ($row->type === 'Pendapatan') {
                $this->pendapatanData[$row->laporan]['detail'][$row->kategori] += $row->total;
                $this->pendapatanData[$row->laporan]['total'] += $row->total;
            }

            if ($row->type === 'Pengeluaran' && !str_starts_with($row->kategori, 'HPP')) {
                $this->pengeluaranData[$row->laporan]['detail'][$row->kategori] += $row->total;
                $this->pengeluaranData[$row->laporan]['total'] += $row->total;
            }
        }
    }

    public function with()
    {
        $totalPendapatan = array_sum(array_column($this->pendapatanData, 'total'));
        $totalPengeluaran = array_sum(array_column($this->pengeluaranData, 'total'));

        return [
            'pendapatanData' => $this->pendapatanData,
            'pengeluaranData' => $this->pengeluaranData,
            'totalPendapatan' => $totalPendapatan,
            'totalPengeluaran' => $totalPengeluaran,
            'labaSebelumPajak' => $totalPendapatan - $totalPengeluaran,
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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                <i class="fas fa-chart-line text-blue-600"></i>Total Laba/Rugi
            </h3>
            <p class="text-2xl font-bold {{ $labaSebelumPajak >= 0 ? 'text-green-700' : 'text-red-700' }} mt-2">
                Rp {{ number_format($labaSebelumPajak, 0, ',', '.') }}
            </p>
        </x-card>
    </div>

    <x-card class="mt-4">
        <h3 class="text-xl font-semibold mb-4"><i class="fas fa-list-ul"></i>Rincian</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="text-lg font-semibold text-green-700 mb-2"><i class="fas fa-arrow-up"></i>Pendapatan per
                    Kelompok</h4>
                <ul class="divide-y divide-gray-200">
                    @foreach ($pendapatanData as $kelompok => $data)
                        <li class="py-2">
                            <div class="flex justify-between cursor-pointer"
                                wire:click="$toggle('expanded.{{ $kelompok }}')">
                                <span class="font-medium">{{ $kelompok }}</span>
                                <span class="text-green-700">Rp {{ number_format($data['total'], 0, ',', '.') }}</span>
                            </div>
                            @if ($expanded[$kelompok] ?? false)
                                <ul class="pl-4 mt-2">
                                    @foreach ($data['detail'] as $sub => $val)
                                        <li class="flex justify-between py-1 text-green-600">
                                            <span>{{ $sub }}</span>
                                            <span>Rp {{ number_format($val, 0, ',', '.') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h4 class="text-lg font-semibold text-red-700 mb-2"><i class="fas fa-arrow-down"></i>Pengeluaran per
                    Kelompok</h4>
                <ul class="divide-y divide-gray-200">
                    @foreach ($pengeluaranData as $kelompok => $data)
                        <li class="py-2">
                            <div class="flex justify-between cursor-pointer"
                                wire:click="$toggle('expanded.{{ $kelompok }}')">
                                <span class="font-medium">{{ $kelompok }}</span>
                                <span class="text-red-700">Rp {{ number_format($data['total'], 0, ',', '.') }}</span>
                            </div>
                            @if ($expanded[$kelompok] ?? false)
                                <ul class="pl-4 mt-2">
                                    @foreach ($data['detail'] as $sub => $val)
                                        <li class="flex justify-between py-1 text-red-600">
                                            <span>{{ $sub }}</span>
                                            <span>Rp {{ number_format($val, 0, ',', '.') }}</span>
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
