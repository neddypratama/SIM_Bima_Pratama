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
        $first = Transaksi::orderBy('tanggal')->first();
        $last = Transaksi::orderByDesc('tanggal')->first();

        // Jika tidak ada transaksi sama sekali, kembalikan array kosong
        if (!$first || !$last) {
            return [
                'asetData' => [],
                'liabilitasData' => []
            ];
        }

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::parse($first->tanggal)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::parse($last->tanggal)->endOfDay();

        $asetData = [];
        $liabilitasData = [];

        /* =====================================================
         | 1. LAPORAN PENDAPATAN & PENGELUARAN (NON HPP)
         ===================================================== */
        $mapping = DB::table('detail_kategoris as dk')
            ->leftJoin('kategoris as k', 'k.detail_kategori_id', '=', 'dk.id')
            ->select('dk.name as laporan', 'dk.type', 'k.name as kategori')
            ->orderBy('dk.id')
            ->get();

        /* =====================================================
         | ASET & LIABILITAS DARI TRANSAKSI
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
         | PIUTANG & HUTANG DARI CLIENT (TOTAL BON)
         ===================================================== */
        $clients = Client::query()
            /* ================= PIUTANG ================= */
            ->withSum([
                'transaksi as piutang_debit' => function ($q) use ($start, $end) {
                    $q->where('type', 'Debit')
                        ->where('status', 'Selesai')
                        ->whereBetween('tanggal', [$start, $end])
                        ->whereHas('details.kategori.detailKategori', function ($q) {
                            $q->where('type', 'Aset');
                        })
                        ->whereHas('details.kategori', function (Builder $q) {
                            $q->where('name', 'not like', '%Stok%')->where('name', 'not like', '%Kas%')->where('name', 'not like', '%Bank%');
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
                            $q->where('name', 'not like', '%Stok%')->where('name', 'not like', '%Kas%')->where('name', 'not like', '%Bank%');
                        });
                }
            ], 'total')
            /* ================= HUTANG ================= */
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

        // ✅ PENDAPATAN
        $curah = DB::table('detail_transaksis as td')
            ->join('kategoris as k', 'k.id', '=', 'td.kategori_id')
            ->join('transaksis as t', 't.id', '=', 'td.transaksi_id')
            ->where('k.name', 'Penjualan Pakan Curah')
            ->where('t.status', 'Selesai')
            ->whereBetween('t.tanggal', [$start, $end])
            ->selectRaw("
                SUM(
                    CASE 
                        WHEN LOWER(t.type) = 'kredit' THEN td.sub_total
                        WHEN LOWER(t.type) = 'debit' THEN -td.sub_total
                        ELSE 0
                    END
                ) as total_pendapatan
            ")
            ->value('total_pendapatan') ?? 0;

        // ✅ HPP
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
                        WHEN LOWER(t.type) = 'debit' THEN td.sub_total
                        WHEN LOWER(t.type) = 'kredit' THEN -td.sub_total
                        ELSE 0
                    END
                ) as total_hpp
            ")
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

        $stokPerJenis = DB::table('stok_batches as sb')
            ->join('detail_transaksis as dt', 'dt.id', '=', 'sb.detail_transaksi_id')
            ->join('barangs as b', 'b.id', '=', 'dt.barang_id')
            ->join('jenis_barangs as jb', 'jb.id', '=', 'b.jenis_id')
            ->select(
                'jb.name as jenis_barang',
                DB::raw('SUM(sb.qty_sisa * sb.harga) as total')
            )
            ->where('sb.qty_sisa', '>', 0)
            ->groupBy('jb.id', 'jb.name')
            ->orderBy('jb.name')
            ->pluck('total', 'jenis_barang')
            ->toArray();

        $asetData['Stok Detail'] = [
            'detail' => [],
            'total' => 0,
        ];

        foreach ($stokPerJenis as $jenis => $total) {
            $asetData['Stok Detail']['detail'][$jenis] = $total;
            $asetData['Stok Detail']['total'] += $total;
        }

        /* =====================================================
        | BANGUN STRUKTUR ASET & LIABILITAS
        ===================================================== */
        foreach ($mapping as $row) {
            if ($row->type === 'Aset') {
                $asetData[$row->laporan]['detail'][$row->kategori] ??= 0;
                $asetData[$row->laporan]['total'] ??= 0;
            }

            if ($row->type === 'Liabilitas') {
                $liabilitasData[$row->laporan]['detail'][$row->kategori] ??= 0;
                $liabilitasData[$row->laporan]['total'] ??= 0;
            }
        }

        /* =====================================================
        | INJECT NILAI ASET
        ===================================================== */
        foreach ($asetFlat as $akun => $nilai) {
            foreach ($asetData as $laporan => &$data) {
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
            foreach ($liabilitasData as $laporan => &$data) {
                if (array_key_exists($akun, $data['detail'])) {
                    $data['detail'][$akun] += $nilai;
                    $data['total'] += $nilai;
                    break;
                }
            }
        }

        // ✅ KEMBALIKAN DATA DALAM BENTUK ARRAY KEY-VALUE
        return [
            'asetData' => $asetData,
            'liabilitasData' => $liabilitasData,
        ];
    }
}