<?php

namespace App\Livewire;

use App\Models\Transaksi;
use App\Models\Kategori;
use App\Models\Client;
use Livewire\Volt\Component;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;
use App\Exports\AsetExport;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component {
    public $startDate;
    public $endDate;

    public $asetData = [];
    public $liabilitasData = [];
    public $expanded = [];

    public function mount()
    {
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
        return Excel::download(new AsetExport($this->endDate), 'aset.xlsx');
    }

    public function generateReport()
    {
        /* =====================================================
         | RANGE TANGGAL
         ===================================================== */
        $first = Transaksi::orderBy('tanggal')->first();
        $last = Transaksi::orderByDesc('tanggal')->first();

        if (!$first || !$last) {
            return;
        }

        $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : Carbon::parse($first->tanggal)->startOfDay();

        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : Carbon::parse($last->tanggal)->endOfDay();

        $this->asetData = [];
        $this->liabilitasData = [];

        /* =====================================================
            1. LAPORAN PENDAPATAN & PENGELUARAN (NON HPP)
        ===================================================== */

        $mapping = DB::table('detail_kategoris as dk')->leftJoin('kategoris as k', 'k.detail_kategori_id', '=', 'dk.id')->select('dk.name as laporan', 'dk.type', 'k.name as kategori')->orderBy('dk.id')->get();

        /* =====================================================
         | ASET & LIABILITAS DARI TRANSAKSI
         ===================================================== */
        $asetFlat = Transaksi::with('details.kategori.detailKategori')
            ->whereBetween('tanggal', [$start, $end])
            ->get()
            ->flatMap->details->filter(fn($d) => $d->kategori->detailKategori?->type === 'Aset')
            ->groupBy(fn($d) => $d->kategori->name)
            ->map(fn($g) => $g->where(fn($i) => strtolower($i->transaksi->type) === 'debit')->sum('sub_total') - $g->where(fn($i) => strtolower($i->transaksi->type) === 'kredit')->sum('sub_total'))
            ->toArray();

        $liabilitasFlat = Transaksi::with('details.kategori.detailKategori')
            ->whereBetween('tanggal', [$start, $end])
            ->get()
            ->flatMap->details->filter(fn($d) => $d->kategori->detailKategori?->type === 'Liabilitas')
            ->groupBy(fn($d) => $d->kategori->name)
            ->map(fn($g) => $g->where(fn($i) => strtolower($i->transaksi->type) === 'kredit')->sum('sub_total') - $g->where(fn($i) => strtolower($i->transaksi->type) === 'debit')->sum('sub_total'))
            ->toArray();

        /* =====================================================
         | PIUTANG & HUTANG DARI CLIENT (TOTAL BON)
         ===================================================== */
        $clients = Client::query()
            /* ================= BON (PIUTANG | DEBIT) ================= */
            ->withSum(
                [
                    'transaksi as bon' => function ($q) {
                        $q->where('type', 'debit')->whereHas('details.kategori', function ($q) {
                            $q->where('name', 'like', 'Piutang%');
                        });
                    },
                ],
                'total',
            )

            /* ================= TITIPAN (HUTANG | KREDIT) ================= */
            ->withSum(
                [
                    'transaksi as titipan' => function ($q) {
                        $q->where('type', 'kredit')->whereHas('details.kategori', function ($q) {
                            $q->where('name', 'like', 'Hutang%');
                        });
                    },
                ],
                'total',
            )
            ->get();

        $piutang = [
            'Piutang Peternak' => 0,
            'Piutang Karyawan' => 0,
            'Piutang Pedagang' => 0,
            'Piutang Tray Diamond /DM' => 0,
            'Piutang Tray Super Buah /SB' => 0,
            'Piutang Tray Random' => 0,
            'Piutang Obat SK' => 0,
            'Piutang Obat Ponggok' => 0,
            'Piutang Obat Random' => 0,
            'Piutang Sentrat SK' => 0,
            'Piutang Sentrat Ponggok' => 0,
            'Piutang Sentrat Random' => 0,
        ];

        $hutang = [
            'Hutang Peternak' => 0,
            'Hutang Karyawan' => 0,
            'Hutang Pedagang' => 0,
            'Hutang Tray Diamond /DM' => 0,
            'Hutang Tray Super Buah /SB' => 0,
            'Hutang Tray Random' => 0,
            'Hutang Obat SK' => 0,
            'Hutang Obat Ponggok' => 0,
            'Hutang Obat Random' => 0,
            'Hutang Sentrat SK' => 0,
            'Hutang Sentrat Ponggok' => 0,
            'Hutang Sentrat Random' => 0,
        ];

        foreach ($clients as $c) {
            $saldo = $c->bon - $c->titipan;
            if ($saldo === 0) {
                continue;
            }

            if ($saldo > 0) {
                if ($c->type === 'Peternak') {
                    $piutang['Piutang Peternak'] += $saldo;
                } elseif ($c->type === 'Pedagang') {
                    $piutang['Piutang Pedagang'] += $saldo;
                } elseif ($c->type === 'Karyawan') {
                    $piutang['Piutang Karyawan'] += $saldo;
                } elseif ($c->type === 'Supplier') {
                    foreach ($piutang as $akun => $_) {
                        if (str_contains($akun, $c->name)) {
                            $piutang[$akun] += $saldo;
                        }
                    }
                }
            }

            if ($saldo < 0) {
                $nilai = abs($saldo);
                if ($c->type === 'Peternak') {
                    $hutang['Hutang Peternak'] += $nilai;
                } elseif ($c->type === 'Pedagang') {
                    $hutang['Hutang Pedagang'] += $nilai;
                } elseif ($c->type === 'Karyawan') {
                    $hutang['Hutang Karyawan'] += $nilai;
                } elseif ($c->type === 'Supplier') {
                    foreach ($hutang as $akun => $_) {
                        if (str_contains($akun, $c->name)) {
                            $hutang[$akun] += $nilai;
                        }
                    }
                }
            }
        }

        /* =====================================================
         | INJECT KE LAPORAN
         ===================================================== */
        foreach ($piutang as $akun => $nilai) {
            $asetFlat[$akun] = $nilai;
        }

        foreach ($hutang as $akun => $nilai) {
            $liabilitasFlat[$akun] = $nilai;
        }

        /* =====================================================
        | BANGUN STRUKTUR ASET & LIABILITAS
        ===================================================== */
        foreach ($mapping as $row) {
            if ($row->type === 'Aset') {
                $this->asetData[$row->laporan]['detail'][$row->kategori] ??= 0;
                $this->asetData[$row->laporan]['total'] ??= 0;
            }

            if ($row->type === 'Liabilitas') {
                $this->liabilitasData[$row->laporan]['detail'][$row->kategori] ??= 0;
                $this->liabilitasData[$row->laporan]['total'] ??= 0;
            }
        }

        /* =====================================================
        | INJECT NILAI ASET
        ===================================================== */
        foreach ($asetFlat as $akun => $nilai) {
            foreach ($this->asetData as $laporan => &$data) {
                if (array_key_exists($akun, $data['detail'])) {
                    $data['detail'][$akun] += $nilai;
                    $data['total'] += $nilai;
                    break;
                }
            }
        }

        /* =====================================================
        | INJECT NILAI LIABILITAS
        ===================================================== */
        foreach ($liabilitasFlat as $akun => $nilai) {
            foreach ($this->liabilitasData as $laporan => &$data) {
                if (array_key_exists($akun, $data['detail'])) {
                    $data['detail'][$akun] += $nilai;
                    $data['total'] += $nilai;
                    break;
                }
            }
        }
    }

    public function with()
    {
        $totalAset = array_sum(array_column($this->asetData, 'total'));
        $totalLiabilitas = array_sum(array_column($this->liabilitasData, 'total'));

        return [
            'asetData' => $this->asetData,
            'liabilitasData' => $this->liabilitasData,
            'totalAset' => $totalAset,
            'totalLiabilitas' => $totalLiabilitas,
            'totalModal' => $totalAset - $totalLiabilitas,
        ];
    }
};

?>

<div class="p-6 space-y-6">
    <x-header title="Laporan Aset" separator>
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
                <i class="fas fa-coins text-green-600"></i> Total Aset
            </h3>
            <p class="text-2xl font-bold text-green-700 mt-2">Rp {{ number_format($totalAset, 0, ',', '.') }}</p>
        </x-card>

        <x-card>
            <h3 class="text-lg font-semibold text-red-800">
                <i class="fas fa-wallet text-red-600"></i> Total Liabilitas
            </h3>
            <p class="text-2xl font-bold text-red-700 mt-2">Rp {{ number_format($totalLiabilitas, 0, ',', '.') }}</p>
        </x-card>

        <x-card>
            <h3 class="text-lg font-semibold text-blue-800">
                <i class="fas fa-wallet text-blue-600"></i> Total Modal
            </h3>
            <p class="text-2xl font-bold text-blue-700 mt-2">Rp {{ number_format($totalModal, 0, ',', '.') }}</p>
        </x-card>
    </div>

    <x-card class="mt-4">
        <h3 class="text-xl font-semibold mb-4"><i class="fas fa-list-ul"></i> Rincian</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Aset -->
            <div>
                <h4 class="text-lg font-semibold text-green-700 mb-2">
                    <i class="fas fa-arrow-up"></i> Aset per Kelompok
                </h4>
                <ul class="divide-y divide-gray-200">
                    @foreach ($asetData as $kelompok => $data)
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

            <!-- Liabilitas -->
            <div>
                <h4 class="text-lg font-semibold text-red-700 mb-2">
                    <i class="fas fa-arrow-down"></i> Liabilitas per Kelompok
                </h4>
                <ul class="divide-y divide-gray-200">
                    @foreach ($liabilitasData as $kelompok => $data)
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
