<?php

namespace App\Services\Reports;

use App\Models\Kategori;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NeracaReportService
{
    public function generate(
        $startDate,
        $endDate,
        array $mappingPendapatan,
        array $mappingPengeluaran,
        array $mappingAset,
        array $mappingLiabilitas,
        array $mappingEkuitas
    ): array {

        $firstTransaction = Transaksi::orderBy('tanggal')->first();
        $lastTransaction  = Transaksi::orderByDesc('tanggal')->first();

        if (!$firstTransaction || !$lastTransaction) {
            return [
                'pendapatan' => [],
                'pengeluaran' => [],
                'aset' => [],
                'liabilitas' => [],
                'ekuitas' => [],
            ];
        }

        $start = $startDate
            ? Carbon::parse($startDate)->startOfDay()
            : Carbon::parse($firstTransaction->tanggal)->startOfDay();

        $end = $endDate
            ? Carbon::parse($endDate)->endOfDay()
            : Carbon::parse($lastTransaction->tanggal)->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Ambil transaksi
        |--------------------------------------------------------------------------
        */

        $complete = DB::table('detail_transaksis as dt')
            ->join('transaksis as t', 't.id', '=', 'dt.transaksi_id')
            ->join('kategoris as k', 'k.id', '=', 'dt.kategori_id')
            ->leftJoin('detail_kategoris as dk', 'dk.id', '=', 'k.detail_kategori_id')
            ->whereBetween('t.tanggal', [$start, $end])
            ->where('t.status', 'Selesai')
            ->where('dt.sub_total', '>', 0)
            ->selectRaw("
                k.name as kategori,
                dk.type,
                SUM(CASE WHEN LOWER(t.type)='debit' THEN dt.sub_total ELSE 0 END) as debit,
                SUM(CASE WHEN LOWER(t.type)='kredit' THEN dt.sub_total ELSE 0 END) as kredit
            ")
            ->groupBy('k.name', 'dk.type')
            ->get()
            ->keyBy(fn($r) => $r->kategori . '_' . $r->type);


        /*
        |--------------------------------------------------------------------------
        | Mapper
        |--------------------------------------------------------------------------
        */

        $mapHierarki = function ($mapping, $type) use ($complete) {

            $result = [];

            foreach ($mapping as $group => $categories) {

                $detail = [];
                $debit = 0;
                $kredit = 0;

                foreach ($categories as $cat) {

                    $row = $complete->get($cat . '_' . $type);

                    if (!$row) {
                        continue;
                    }

                    $detail[] = [
                        'kategori' => $cat,
                        'type' => $type,
                        'debit' => $row->debit ?? 0,
                        'kredit' => $row->kredit ?? 0,
                    ];

                    $debit += $row->debit ?? 0;
                    $kredit += $row->kredit ?? 0;
                }

                $result[] = [
                    'group'   => $group,
                    'debit'   => $debit,
                    'kredit'  => $kredit,
                    'details' => $detail,
                ];
            }

            return $result;
        };

        return [

            'pendapatan' => $mapHierarki($mappingPendapatan, 'Pendapatan'),

            'pengeluaran' => $mapHierarki($mappingPengeluaran, 'Pengeluaran'),

            'aset' => $mapHierarki($mappingAset, 'Aset'),

            'liabilitas' => $mapHierarki($mappingLiabilitas, 'Liabilitas'),

            'ekuitas' => $mapHierarki($mappingEkuitas, 'Ekuitas'),

        ];
    }
}