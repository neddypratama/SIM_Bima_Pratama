<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LabaRugiExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected string $startDate;
    protected string $endDate;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    /* ================= HEADING ================= */
    public function headings(): array
    {
        return [
            ['Laporan Laba Rugi'],
            ['Periode: ' .
                Carbon::parse($this->startDate)->format('d M Y') .
                ' - ' .
                Carbon::parse($this->endDate)->format('d M Y')
            ],
            [],
            ['Kategori', 'Tipe', 'Total (Rp)'],
        ];
    }

    /* ================= DATA ================= */
    public function array(): array
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end   = Carbon::parse($this->endDate)->endOfDay();

        $rows = [];

        /* =====================================================
            1. MASTER LAPORAN (DEFAULT 0)
        ===================================================== */
        $laporans = DB::table('detail_kategoris as dk')
            ->leftJoin('kategoris as k', 'k.detail_kategori_id', '=', 'dk.id')
            ->select('dk.name as laporan', 'dk.type', 'k.name as kategori')
            ->orderBy('dk.id')
            ->get();

        $pendapatan = [];
        $pengeluaran = [];

        foreach ($laporans as $row) {
            if ($row->type === 'Pendapatan') {
                $pendapatan[$row->laporan]['detail'][$row->kategori] = 0;
                $pendapatan[$row->laporan]['total'] ??= 0;
            }

            if ($row->type === 'Pengeluaran' && !str_starts_with($row->kategori ?? '', 'HPP')) {
                $pengeluaran[$row->laporan]['detail'][$row->kategori] = 0;
                $pengeluaran[$row->laporan]['total'] ??= 0;
            }
        }

        /* =====================================================
            2. ISI DATA TRANSAKSI (NON HPP)
        ===================================================== */
        $trxRows = DB::table('detail_transaksis as dt')
            ->join('kategoris as k', 'k.id', '=', 'dt.kategori_id')
            ->join('detail_kategoris as dk', 'dk.id', '=', 'k.detail_kategori_id')
            ->join('transaksis as t', 't.id', '=', 'dt.transaksi_id')
            ->whereBetween('t.tanggal', [$start, $end])
            ->select(
                'dk.name as laporan',
                'dk.type',
                'k.name as kategori',
                DB::raw('SUM(dt.sub_total) as total')
            )
            ->groupBy('dk.name', 'dk.type', 'k.name')
            ->get();

        foreach ($trxRows as $row) {
            if ($row->type === 'Pendapatan') {
                $pendapatan[$row->laporan]['detail'][$row->kategori] += $row->total;
                $pendapatan[$row->laporan]['total'] += $row->total;
            }

            if ($row->type === 'Pengeluaran' && !str_starts_with($row->kategori, 'HPP')) {
                $pengeluaran[$row->laporan]['detail'][$row->kategori] += $row->total;
                $pengeluaran[$row->laporan]['total'] += $row->total;
            }
        }

        /* =====================================================
            3. HPP (GROUPING PALING ATAS)
        ===================================================== */
        $hppKelompok = [
            'HPP Telur' => ['HPP Telur Horn', 'HPP Telur Bebek', 'HPP Telur Puyuh', 'HPP Telur Arab', 'HPP Telur Asin'],
            'HPP Pakan' => ['HPP Pakan Sentrat/Pabrikan', 'HPP Pakan Kucing'],
            'HPP Obat'  => ['HPP Obat-Obatan'],
            'HPP Eggtray' => ['HPP Tray'],
        ];

        $hppResults = DB::table('detail_transaksis as td')
            ->join('kategoris as k', 'k.id', '=', 'td.kategori_id')
            ->join('barangs as b', 'b.id', '=', 'td.barang_id')
            ->join('jenis_barangs as jb', 'jb.id', '=', 'b.jenis_id')
            ->join('transaksis as t', 't.id', '=', 'td.transaksi_id')
            ->select(
                DB::raw("CONCAT('HPP ', jb.name) AS hpp_name"),
                DB::raw('SUM(td.sub_total) AS total_hpp')
            )
            ->where('k.name', 'HPP')
            ->whereBetween('t.tanggal', [$start, $end])
            ->groupBy('jb.name')
            ->get()
            ->keyBy('hpp_name');

        foreach ($hppKelompok as $kelompok => $list) {
            $total = 0;
            foreach ($list as $hppName) {
                $nilai = $hppResults[$hppName]->total_hpp ?? 0;
                $pengeluaran[$kelompok]['detail'][$hppName] = $nilai;
                $total += $nilai;
            }
            $pengeluaran[$kelompok]['total'] = $total;
        }

        /* =====================================================
            4. BENTUK ROW EXCEL
        ===================================================== */

        // Pendapatan
        $rows[] = ['Pendapatan', '', ''];
        $totalPendapatan = 0;

        foreach ($pendapatan as $laporan => $data) {
            foreach ($data['detail'] as $kategori => $nilai) {
                $rows[] = [$kategori, 'Pendapatan', $nilai];
            }
            $totalPendapatan += $data['total'];
        }

        $rows[] = ['Total Pendapatan', '', $totalPendapatan];
        $rows[] = [];

        // Pengeluaran
        $rows[] = ['Pengeluaran', '', ''];
        $totalPengeluaran = 0;

        foreach ($pengeluaran as $laporan => $data) {
            foreach ($data['detail'] as $kategori => $nilai) {
                $rows[] = [$kategori, 'Pengeluaran', $nilai];
            }
            $totalPengeluaran += $data['total'];
        }

        $rows[] = ['Total Pengeluaran', '', $totalPengeluaran];
        $rows[] = [];

        // Ringkasan
        $rows[] = ['Total Laba/Rugi', '', $totalPendapatan - $totalPengeluaran];

        return $rows;
    }

    /* ================= SHEET ================= */
    public function title(): string
    {
        return 'Laporan Laba Rugi';
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
