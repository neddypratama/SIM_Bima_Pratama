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

        $asetFlat = DB::table('detail_transaksis as dt')
            ->join('transaksis as t','t.id','=','dt.transaksi_id')
            ->join('kategoris as k','k.id','=','dt.kategori_id')
            ->join('detail_kategoris as dk','dk.id','=','k.detail_kategori_id')

            ->whereBetween('t.tanggal',[$start,$end])
            ->where('t.status','Selesai')
            ->where('dk.type','Aset')
            ->where('k.name','!=','Penyesuaian Stok')

            ->selectRaw("
                k.name kategori,
                SUM(
                    CASE
                        WHEN LOWER(t.type)='debit'
                        THEN dt.sub_total
                        ELSE -dt.sub_total
                    END
                ) total
            ")
            ->groupBy('k.name')
            ->pluck('total','kategori')
            ->toArray();

        /* =====================================================
         | LIABILITAS
         ===================================================== */

        $liabilitasFlat = DB::table('detail_transaksis as dt')
            ->join('transaksis as t','t.id','=','dt.transaksi_id')
            ->join('kategoris as k','k.id','=','dt.kategori_id')
            ->join('detail_kategoris as dk','dk.id','=','k.detail_kategori_id')

            ->whereBetween('t.tanggal',[$start,$end])
            ->where('t.status','Selesai')
            ->where('dk.type','Liabilitas')

            ->selectRaw("
                k.name kategori,
                SUM(
                    CASE
                        WHEN LOWER(t.type)='kredit'
                        THEN dt.sub_total
                        ELSE -dt.sub_total
                    END
                ) total
            ")
            ->groupBy('k.name')
            ->pluck('total','kategori')
            ->toArray();

        /* =====================================================
         | PIUTANG & HUTANG
         ===================================================== */

        $clients = DB::table('transaksis as t')
            ->join('clients as c', 'c.id', '=', 't.client_id')
            ->join('detail_transaksis as dt', 'dt.transaksi_id', '=', 't.id')
            ->join('kategoris as k', 'k.id', '=', 'dt.kategori_id')
            ->join('detail_kategoris as dk', 'dk.id', '=', 'k.detail_kategori_id')
            ->where('t.status', 'Selesai')
            ->whereBetween('t.tanggal', [$start, $end])
            ->groupBy('c.id', 'c.name', 'c.type')
            ->selectRaw("
                c.id,
                c.name,
                c.type,

                SUM(
                    CASE
                        WHEN dk.type='Aset'
                            AND t.type='Debit'
                            AND k.name NOT LIKE '%Stok%'
                            AND k.name NOT LIKE '%Kas%'
                            AND k.name NOT LIKE '%Bank%'
                        THEN t.total
                        ELSE 0
                    END
                ) AS piutang_debit,

                SUM(
                    CASE
                        WHEN dk.type='Aset'
                            AND t.type='Kredit'
                            AND k.name NOT LIKE '%Stok%'
                            AND k.name NOT LIKE '%Kas%'
                            AND k.name NOT LIKE '%Bank%'
                        THEN t.total
                        ELSE 0
                    END
                ) AS piutang_kredit,

                SUM(
                    CASE
                        WHEN dk.type='Liabilitas'
                            AND t.type='Kredit'
                        THEN t.total
                        ELSE 0
                    END
                ) AS hutang_kredit,

                SUM(
                    CASE
                        WHEN dk.type='Liabilitas'
                            AND t.type='Debit'
                        THEN t.total
                        ELSE 0
                    END
                ) AS hutang_debit
            ")
            ->get();

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

            $saldoPiutang =
                ($c->piutang_debit ?? 0)
                - ($c->piutang_kredit ?? 0);

            $saldoHutang =
                ($c->hutang_kredit ?? 0)
                - ($c->hutang_debit ?? 0);

            $saldo = $saldoPiutang - $saldoHutang;

            if ($saldo == 0) {
                continue;
            }

            if ($saldo > 0) {

                if ($c->type == 'Peternak') {
                    $piutang['Piutang Peternak'] += $saldo;
                } elseif ($c->type == 'Pedagang') {
                    $piutang['Piutang Pedagang'] += $saldo;
                } elseif ($c->type == 'Karyawan') {
                    $piutang['Piutang Karyawan'] += $saldo;
                } elseif ($c->type == 'Supplier') {

                    foreach ($piutang as $akun => $_) {
                        if (str_contains($akun, $c->name)) {
                            $piutang[$akun] += $saldo;
                        }
                    }
                }
            }

            if ($saldo < 0) {

                $nilai = abs($saldo);

                if ($c->type == 'Peternak') {
                    $hutang['Hutang Peternak'] += $nilai;
                } elseif ($c->type == 'Pedagang') {
                    $hutang['Hutang Pedagang'] += $nilai;
                } elseif ($c->type == 'Karyawan') {
                    $hutang['Hutang Karyawan'] += $nilai;
                } elseif ($c->type == 'Supplier') {

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