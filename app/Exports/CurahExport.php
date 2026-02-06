<?php

namespace App\Exports;

use App\Models\Transaksi;
use App\Models\Barang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CurahExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected Carbon $start;
    protected Carbon $end;

    public function __construct(?string $startDate, ?string $endDate)
    {
        $first = Transaksi::orderBy('tanggal')->first();
        $last  = Transaksi::orderByDesc('tanggal')->first();

        $this->start = $startDate
            ? Carbon::parse($startDate)->startOfDay()
            : Carbon::parse($first?->tanggal)->startOfDay();

        $this->end = $endDate
            ? Carbon::parse($endDate)->endOfDay()
            : Carbon::parse($last?->tanggal)->endOfDay();
    }

    public function headings(): array
    {
        return [
            ['LAPORAN LABA RUGI – PAKAN CURAH'],
            ['Periode: ' . $this->start->format('d M Y') . ' s/d ' . $this->end->format('d M Y')],
            [],
            ['Keterangan', 'Debit (Rp)', 'Kredit (Rp)'],
        ];
    }

    public function array(): array
    {
        $rows = [];

        /* =====================================================
         | MASTER BARANG CURAH
         ===================================================== */
        $barangCurah = Barang::whereHas('jenis', fn($q) => $q->where('name', 'Pakan Curah'))
            ->pluck('name')
            ->toArray();

        /* =====================================================
         | STOK CURAH
         ===================================================== */
        $stokCurah = Transaksi::with(['details.kategori', 'details.barang.jenis'])
            ->whereHas('details.kategori', fn($q) => $q->where('name', 'Stok Pakan'))
            ->whereHas('details.barang.jenis', fn($q) => $q->where('name', 'Pakan Curah'))
            ->whereBetween('tanggal', [$this->start, $this->end])
            ->get()
            ->flatMap(fn($trx) => $trx->details)
            ->filter(fn($d) => $d->barang?->jenis?->name === 'Pakan Curah')
            ->reduce(function ($carry, $d) {
                return $carry + (
                    strtolower($d->transaksi->type) === 'debit'
                        ? $d->sub_total
                        : -$d->sub_total
                );
            }, 0);

        /* =====================================================
         | PENDAPATAN (KREDIT - DEBIT)
         ===================================================== */
        $pendapatanDB = Transaksi::with('details.barang')
            ->whereHas('details.kategori', fn($q) => $q->where('name', 'Penjualan Pakan Curah'))
            ->whereBetween('tanggal', [$this->start, $this->end])
            ->get()
            ->flatMap(fn($trx) => $trx->details)
            ->groupBy(fn($d) => $d->barang->name)
            ->map(fn($g) =>
                $g->where(fn($d) => strtolower($d->transaksi->type) === 'kredit')->sum('sub_total')
                -
                $g->where(fn($d) => strtolower($d->transaksi->type) === 'debit')->sum('sub_total')
            );

        $totalPendapatan = 0;

        /* =====================================================
         | HPP (DEBIT - KREDIT)
         ===================================================== */
        $hppDB = DB::table('detail_transaksis as td')
            ->join('kategoris as k', 'k.id', '=', 'td.kategori_id')
            ->join('transaksis as t', 't.id', '=', 'td.transaksi_id')
            ->join('barangs as b', 'b.id', '=', 'td.barang_id')
            ->join('jenis_barangs as jb', 'jb.id', '=', 'b.jenis_id')
            ->where('k.name', 'HPP')
            ->where('jb.name', 'Pakan Curah')
            ->whereBetween('t.tanggal', [$this->start, $this->end])
            ->select(
                'b.name',
                DB::raw("SUM(CASE WHEN LOWER(t.type)='debit' THEN td.sub_total ELSE 0 END) AS debit"),
                DB::raw("SUM(CASE WHEN LOWER(t.type)='kredit' THEN td.sub_total ELSE 0 END) AS kredit")
            )
            ->groupBy('b.name')
            ->get()
            ->keyBy('name');

        $totalHPP = 0;

        /* =====================================================
         | OUTPUT EXCEL
         ===================================================== */

        $rows[] = ['STOK PAKAN CURAH', $stokCurah, ''];
        $rows[] = [];

        // Pendapatan
        $rows[] = ['PENDAPATAN', '', ''];
        foreach ($barangCurah as $barang) {
            $nilai = $pendapatanDB[$barang] ?? 0;
            $rows[] = [$barang, '', $nilai];
            $totalPendapatan += $nilai;
        }
        $rows[] = ['TOTAL PENDAPATAN', '', $totalPendapatan];
        $rows[] = [];

        // HPP
        $rows[] = ['HPP PAKAN CURAH', '', ''];
        foreach ($barangCurah as $barang) {
            $d = $hppDB[$barang]->debit ?? 0;
            $k = $hppDB[$barang]->kredit ?? 0;
            $nilai = $d - $k;
            $rows[] = [$barang, $nilai, ''];
            $totalHPP += $nilai;
        }
        $rows[] = ['TOTAL HPP', $totalHPP, ''];
        $rows[] = [];

        // Laba Bersih
        $rows[] = ['LABA BERSIH', '', $totalPendapatan - $totalHPP];

        return $rows;
    }

    public function title(): string
    {
        return 'Laba Rugi Curah';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        $sheet->getStyle('A4:C4')->getFont()->setBold(true);

        return [];
    }
}
