<?php

namespace App\Livewire;

use App\Models\Transaksi;
use App\Models\Barang;
use App\Models\Client;
use Livewire\Volt\Component;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;
use App\Exports\AsetExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Builder;

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
            ->where('status', 'Selesai')
            ->get()
            ->flatMap->details->filter(fn($d) => $d->kategori->detailKategori?->type === 'Aset' && $d->kategori?->name !== 'Penyesuaian Stok')
            ->groupBy(fn($d) => $d->kategori->name)
            ->map(fn($g) => $g->where(fn($i) => strtolower($i->transaksi->type) === 'debit')->sum('sub_total') - $g->where(fn($i) => strtolower($i->transaksi->type) === 'kredit')->sum('sub_total'))
            ->toArray();

        $liabilitasFlat = Transaksi::with('details.kategori.detailKategori')
            ->whereBetween('tanggal', [$start, $end])
            ->where('status', 'Selesai')
            ->get()
            ->flatMap->details->filter(fn($d) => $d->kategori->detailKategori?->type === 'Liabilitas')
            ->groupBy(fn($d) => $d->kategori->name)
            ->map(fn($g) => $g->where(fn($i) => strtolower($i->transaksi->type) === 'kredit')->sum('sub_total') - $g->where(fn($i) => strtolower($i->transaksi->type) === 'debit')->sum('sub_total'))
            ->toArray();

        /* =====================================================
         | PIUTANG & HUTANG DARI CLIENT (TOTAL BON)
         ===================================================== */
        $clients = Client::query()

            /* ================= PIUTANG ================= */
            ->withSum(
                [
                    'transaksi as piutang_debit' => function ($q) {
                        $q->where('type', 'Debit')
                            ->where('status', 'Selesai')
                            ->whereHas('details.kategori.detailKategori', function ($q) {
                                $q->where('type', 'Aset');
                            })
                            ->whereHas('details.kategori', function (Builder $q) {
                                $q->where('name', 'not like', '%Stok%')->where('name', 'not like', '%Kas%')->where('name', 'not like', '%Bank%');
                            });
                    },
                ],
                'total',
            )

            ->withSum(
                [
                    'transaksi as piutang_kredit' => function ($q) {
                        $q->where('type', 'Kredit')
                            ->where('status', 'Selesai')
                            ->whereHas('details.kategori.detailKategori', function ($q) {
                                $q->where('type', 'Aset');
                            })
                            ->whereHas('details.kategori', function (Builder $q) {
                                $q->where('name', 'not like', '%Stok%')->where('name', 'not like', '%Kas%')->where('name', 'not like', '%Bank%');
                            });
                    },
                ],
                'total',
            )

            /* ================= HUTANG ================= */
            ->withSum(
                [
                    'transaksi as hutang_kredit' => function ($q) {
                        $q->where('type', 'Kredit')
                            ->where('status', 'Selesai')
                            ->whereHas('details.kategori.detailKategori', function ($q) {
                                $q->where('type', 'Liabilitas');
                            });
                    },
                ],
                'total',
            )

            ->withSum(
                [
                    'transaksi as hutang_debit' => function ($q) {
                        $q->where('type', 'Debit')
                            ->where('status', 'Selesai')
                            ->whereHas('details.kategori.detailKategori', function ($q) {
                                $q->where('type', 'Liabilitas');
                            });
                    },
                ],
                'total',
            )
            ->get()
            ->map(function ($client) {
                // 🔥 ASET → Debit - Kredit
                $client->saldo_piutang = ($client->piutang_debit ?? 0) - ($client->piutang_kredit ?? 0);

                // 🔥 LIABILITAS → Kredit - Debit
                $client->saldo_hutang = ($client->hutang_kredit ?? 0) - ($client->hutang_debit ?? 0);

                return $client;
            });

        $piutang = [
            'Piutang Peternak' => 0,
            'Piutang Karyawan' => 0,
            'Piutang Pedagang' => 0,
            'Supplier Bp.Supriyadi' => 0,
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
            'Saldo Bp.Supriyadi' => 0,
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
            $saldo = $c->saldo_piutang - $c->saldo_hutang;

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

        // ✅ PENDAPATAN (BENAR)
        $curah =
            DB::table('detail_transaksis as td')
                ->join('kategoris as k', 'k.id', '=', 'td.kategori_id')
                ->join('transaksis as t', 't.id', '=', 'td.transaksi_id')
                ->where('k.name', 'Penjualan Pakan Curah')
                ->where('t.status', 'Selesai')
                ->whereBetween('t.tanggal', [$start, $end])
                ->selectRaw(
                    "
            SUM(
                CASE 
                    WHEN LOWER(t.type) = 'kredit' THEN td.sub_total
                    WHEN LOWER(t.type) = 'debit' THEN -td.sub_total
                    ELSE 0
                END
            ) as total_pendapatan
        ",
                )
                ->value('total_pendapatan') ?? 0;

        // ✅ HPP (SUDAH BENAR)
        $hpp =
            DB::table('detail_transaksis as td')
                ->join('kategoris as k', 'k.id', '=', 'td.kategori_id')
                ->join('transaksis as t', 't.id', '=', 'td.transaksi_id')
                ->join('barangs as b', 'b.id', '=', 'td.barang_id')
                ->join('jenis_barangs as jb', 'jb.id', '=', 'b.jenis_id')
                ->where('k.name', 'HPP')
                ->where('jb.name', 'Pakan Curah')
                ->where('t.status', 'Selesai')
                ->whereBetween('t.tanggal', [$start, $end])
                ->selectRaw(
                    "
            SUM(
                CASE 
                    WHEN LOWER(t.type) = 'debit' THEN td.sub_total
                    WHEN LOWER(t.type) = 'kredit' THEN -td.sub_total
                    ELSE 0
                END
            ) as total_hpp
        ",
                )
                ->value('total_hpp') ?? 0;

        $saldo = $hutang['Saldo Bp.Supriyadi'] + ($curah - $hpp);
        $hutang['Saldo Bp.Supriyadi'] = $saldo;

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
