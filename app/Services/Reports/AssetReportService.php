<?php

namespace App\Services\Reports;

use App\Models\Client;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AssetReportService
{
    public function generate($startDate = null, $endDate = null): array
    {
        /* =====================================================
         | RANGE TANGGAL
         ===================================================== */

        $first = Transaksi::orderBy('tanggal')->first();
        $last = Transaksi::orderByDesc('tanggal')->first();

        if (!$first || !$last) {
            return [
                'asetData' => [],
                'liabilitasData' => [],
            ];
        }

        $start = $startDate
            ? Carbon::parse($startDate)->startOfDay()
            : Carbon::parse($first->tanggal)->startOfDay();

        $end = $endDate
            ? Carbon::parse($endDate)->endOfDay()
            : Carbon::parse($last->tanggal)->endOfDay();

        $asetData = [];
        $liabilitasData = [];

        /* =====================================================
         | MAPPING LAPORAN
         ===================================================== */

        $mapping = DB::table('detail_kategoris as dk')
            ->leftJoin('kategoris as k', 'k.detail_kategori_id', '=', 'dk.id')
            ->select(
                'dk.name as laporan',
                'dk.type',
                'k.name as kategori'
            )
            ->orderBy('dk.id')
            ->get();

        /* =====================================================
         | ASET
         ===================================================== */

        $asetFlat = Transaksi::with('details.kategori.detailKategori')
            ->whereBetween('tanggal', [$start, $end])
            ->where('status', 'Selesai')
            ->get()
            ->flatMap->details
            ->filter(fn($d) =>
                $d->kategori->detailKategori?->type === 'Aset'
                && $d->kategori?->name !== 'Penyesuaian Stok'
            )
            ->groupBy(fn($d) => $d->kategori->name)
            ->map(fn($g) =>
                $g->where(fn($i) => strtolower($i->transaksi->type) == 'debit')->sum('sub_total')
                -
                $g->where(fn($i) => strtolower($i->transaksi->type) == 'kredit')->sum('sub_total')
            )
            ->toArray();

        /* =====================================================
         | LIABILITAS
         ===================================================== */

        $liabilitasFlat = Transaksi::with('details.kategori.detailKategori')
            ->whereBetween('tanggal', [$start, $end])
            ->where('status', 'Selesai')
            ->get()
            ->flatMap->details
            ->filter(fn($d) =>
                $d->kategori->detailKategori?->type === 'Liabilitas'
            )
            ->groupBy(fn($d) => $d->kategori->name)
            ->map(fn($g) =>
                $g->where(fn($i) => strtolower($i->transaksi->type) == 'kredit')->sum('sub_total')
                -
                $g->where(fn($i) => strtolower($i->transaksi->type) == 'debit')->sum('sub_total')
            )
            ->toArray();

        /* =====================================================
         | PIUTANG & HUTANG
         ===================================================== */

        $clients = Client::query()

            ->withSum([
                'transaksi as piutang_debit' => function ($q) use ($start, $end) {
                    $q->where('type', 'Debit')
                        ->where('status', 'Selesai')
                        ->whereBetween('tanggal', [$start, $end])
                        ->whereHas('details.kategori.detailKategori', function ($q) {
                            $q->where('type', 'Aset');
                        })
                        ->whereHas('details.kategori', function (Builder $q) {
                            $q->where('name', 'not like', '%Stok%')
                                ->where('name', 'not like', '%Kas%')
                                ->where('name', 'not like', '%Bank%');
                        });
                }
            ], 'total')

            ->withSum([
                'transaksi as piutang_kredit' => function ($q) use ($start, $end) {
                    $q->where('type', 'Kredit')
                        ->where('status', 'Selesai')
                        ->whereBetween('tanggal', [$start, $end])
                        ->whereHas('details.kategori.detailKategori', function ($q) {
                            $q->where('type', 'Aset');
                        })
                        ->whereHas('details.kategori', function (Builder $q) {
                            $q->where('name', 'not like', '%Stok%')
                                ->where('name', 'not like', '%Kas%')
                                ->where('name', 'not like', '%Bank%');
                        });
                }
            ], 'total')

            ->withSum([
                'transaksi as hutang_kredit' => function ($q) use ($start, $end) {
                    $q->where('type', 'Kredit')
                        ->where('status', 'Selesai')
                        ->whereBetween('tanggal', [$start, $end])
                        ->whereHas('details.kategori.detailKategori', function ($q) {
                            $q->where('type', 'Liabilitas');
                        });
                }
            ], 'total')

            ->withSum([
                'transaksi as hutang_debit' => function ($q) use ($start, $end) {
                    $q->where('type', 'Debit')
                        ->where('status', 'Selesai')
                        ->whereBetween('tanggal', [$start, $end])
                        ->whereHas('details.kategori.detailKategori', function ($q) {
                            $q->where('type', 'Liabilitas');
                        });
                }
            ], 'total')

            ->get()
            ->map(function ($client) {

                $client->saldo_piutang =
                    ($client->piutang_debit ?? 0)
                    -
                    ($client->piutang_kredit ?? 0);

                $client->saldo_hutang =
                    ($client->hutang_kredit ?? 0)
                    -
                    ($client->hutang_debit ?? 0);

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

            if ($saldo == 0) {
                continue;
            }

            if ($saldo > 0) {

                if ($c->type == 'Peternak')
                    $piutang['Piutang Peternak'] += $saldo;

                elseif ($c->type == 'Pedagang')
                    $piutang['Piutang Pedagang'] += $saldo;

                elseif ($c->type == 'Karyawan')
                    $piutang['Piutang Karyawan'] += $saldo;

                elseif ($c->type == 'Supplier') {

                    foreach ($piutang as $akun => $_) {

                        if (str_contains($akun, $c->name)) {

                            $piutang[$akun] += $saldo;
                        }
                    }
                }
            }

            if ($saldo < 0) {

                $nilai = abs($saldo);

                if ($c->type == 'Peternak')
                    $hutang['Hutang Peternak'] += $nilai;

                elseif ($c->type == 'Pedagang')
                    $hutang['Hutang Pedagang'] += $nilai;

                elseif ($c->type == 'Karyawan')
                    $hutang['Hutang Karyawan'] += $nilai;

                elseif ($c->type == 'Supplier') {

                    foreach ($hutang as $akun => $_) {

                        if (str_contains($akun, $c->name)) {

                            $hutang[$akun] += $nilai;
                        }
                    }
                }
            }
        }

        /* =====================================================
         | SALDO SUPRIYADI
         ===================================================== */

        $curah = DB::table('detail_transaksis as td')
            ->join('kategoris as k', 'k.id', '=', 'td.kategori_id')
            ->join('transaksis as t', 't.id', '=', 'td.transaksi_id')
            ->where('k.name', 'Penjualan Pakan Curah')
            ->where('t.status', 'Selesai')
            ->whereBetween('t.tanggal', [$start, $end])
            ->selectRaw("
                SUM(
                    CASE
                        WHEN LOWER(t.type)='kredit' THEN td.sub_total
                        WHEN LOWER(t.type)='debit' THEN -td.sub_total
                    END
                ) total
            ")
            ->value('total') ?? 0;

        $hpp = DB::table('detail_transaksis as td')
            ->join('kategoris as k', 'k.id', '=', 'td.kategori_id')
            ->join('transaksis as t', 't.id', '=', 'td.transaksi_id')
            ->join('barangs as b', 'b.id', '=', 'td.barang_id')
            ->join('jenis_barangs as jb', 'jb.id', '=', 'b.jenis_id')
            ->where('k.name', 'HPP')
            ->where('jb.name', 'Pakan Curah')
            ->where('t.status', 'Selesai')
            ->whereBetween('t.tanggal', [$start, $end])
            ->selectRaw("
                SUM(
                    CASE
                        WHEN LOWER(t.type)='debit' THEN td.sub_total
                        WHEN LOWER(t.type)='kredit' THEN -td.sub_total
                    END
                ) total
            ")
            ->value('total') ?? 0;

        $hutang['Saldo Bp.Supriyadi'] += ($curah - $hpp);

        foreach ($piutang as $akun => $nilai) {
            $asetFlat[$akun] = $nilai;
        }

        foreach ($hutang as $akun => $nilai) {
            $liabilitasFlat[$akun] = $nilai;
        }

        foreach ($mapping as $row) {

            if ($row->type == 'Aset') {

                $asetData[$row->laporan]['detail'][$row->kategori] ??= 0;
                $asetData[$row->laporan]['total'] ??= 0;
            }

            if ($row->type == 'Liabilitas') {

                $liabilitasData[$row->laporan]['detail'][$row->kategori] ??= 0;
                $liabilitasData[$row->laporan]['total'] ??= 0;
            }
        }

        foreach ($asetFlat as $akun => $nilai) {

            foreach ($asetData as &$data) {

                if (array_key_exists($akun, $data['detail'])) {

                    $data['detail'][$akun] += $nilai;
                    $data['total'] += $nilai;
                    break;
                }
            }
        }

        foreach ($liabilitasFlat as $akun => $nilai) {

            foreach ($liabilitasData as &$data) {

                if (array_key_exists($akun, $data['detail'])) {

                    $data['detail'][$akun] += $nilai;
                    $data['total'] += $nilai;
                    break;
                }
            }
        }

        return [
            'asetData' => $asetData,
            'liabilitasData' => $liabilitasData,
        ];
    }
}