<?php

namespace App\Exports;

use App\Models\Transaksi;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AsetExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected ?string $startDate;
    protected ?string $endDate;

    public function __construct(?string $startDate = null, ?string $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    /* ===============================
     | HEADER
     =============================== */
    public function headings(): array
    {
        return [
            ['Laporan Keuangan (Aset & Liabilitas)'],
            [
                'Periode: '
                . Carbon::parse($this->startDate)->format('d M Y')
                . ' - '
                . Carbon::parse($this->endDate)->format('d M Y')
            ],
            [],
            ['Akun', 'Kelompok', 'Total (Rp)'],
        ];
    }

    /* ===============================
     | DATA
     =============================== */
    public function array(): array
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end   = Carbon::parse($this->endDate)->endOfDay();

        /* =====================================================
         | MAPPING LAPORAN
         ===================================================== */
        $mapping = DB::table('detail_kategoris as dk')
            ->leftJoin('kategoris as k', 'k.detail_kategori_id', '=', 'dk.id')
            ->select('dk.name as laporan', 'dk.type', 'k.name as kategori')
            ->orderBy('dk.id')
            ->get();

        /* =====================================================
         | ASET & LIABILITAS DARI TRANSAKSI
         ===================================================== */
        $asetFlat = Transaksi::with('details.kategori.detailKategori')
            ->whereBetween('tanggal', [$start, $end])
            ->get()
            ->flatMap->details
            ->filter(fn ($d) => $d->kategori->detailKategori?->type === 'Aset')
            ->groupBy(fn ($d) => $d->kategori->name)
            ->map(fn ($g) =>
                $g->where(fn ($i) => strtolower($i->transaksi->type) === 'debit')->sum('sub_total')
                -
                $g->where(fn ($i) => strtolower($i->transaksi->type) === 'kredit')->sum('sub_total')
            )
            ->toArray();

        $liabilitasFlat = Transaksi::with('details.kategori.detailKategori')
            ->whereBetween('tanggal', [$start, $end])
            ->get()
            ->flatMap->details
            ->filter(fn ($d) => $d->kategori->detailKategori?->type === 'Liabilitas')
            ->groupBy(fn ($d) => $d->kategori->name)
            ->map(fn ($g) =>
                $g->where(fn ($i) => strtolower($i->transaksi->type) === 'kredit')->sum('sub_total')
                -
                $g->where(fn ($i) => strtolower($i->transaksi->type) === 'debit')->sum('sub_total')
            )
            ->toArray();

        /* =====================================================
         | PIUTANG & HUTANG DARI CLIENT
         ===================================================== */
        $clients = Client::query()
            ->withSum(
                ['transaksi as piutang_debit' => fn ($q) =>
                    $q->where('type', 'debit')
                      ->whereHas('details.kategori', fn ($q) => $q->where('name', 'like', 'Piutang%'))
                ],
                'total'
            )
            ->withSum(
                ['transaksi as piutang_kredit' => fn ($q) =>
                    $q->where('type', 'kredit')
                      ->whereHas('details.kategori', fn ($q) => $q->where('name', 'like', 'Piutang%'))
                ],
                'total'
            )
            ->withSum(
                ['transaksi as hutang_kredit' => fn ($q) =>
                    $q->where('type', 'kredit')
                      ->whereHas('details.kategori', fn ($q) => $q->where('name', 'like', 'Hutang%'))
                ],
                'total'
            )
            ->withSum(
                ['transaksi as hutang_debit' => fn ($q) =>
                    $q->where('type', 'debit')
                      ->whereHas('details.kategori', fn ($q) => $q->where('name', 'like', 'Hutang%'))
                ],
                'total'
            )
            ->get();

        foreach ($clients as $c) {
            $saldo = ($c->piutang_debit - $c->piutang_kredit)
                   - ($c->hutang_kredit - $c->hutang_debit);

            if ($saldo > 0) {
                $asetFlat['Piutang ' . $c->type] =
                    ($asetFlat['Piutang ' . $c->type] ?? 0) + $saldo;
            }

            if ($saldo < 0) {
                $liabilitasFlat['Hutang ' . $c->type] =
                    ($liabilitasFlat['Hutang ' . $c->type] ?? 0) + abs($saldo);
            }
        }

        /* =====================================================
         | SUSUN STRUKTUR
         ===================================================== */
        $asetData = [];
        $liabilitasData = [];

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

        foreach ($asetFlat as $akun => $nilai) {
            foreach ($asetData as &$lap) {
                if (array_key_exists($akun, $lap['detail'])) {
                    $lap['detail'][$akun] += $nilai;
                    $lap['total'] += $nilai;
                }
            }
        }

        foreach ($liabilitasFlat as $akun => $nilai) {
            foreach ($liabilitasData as &$lap) {
                if (array_key_exists($akun, $lap['detail'])) {
                    $lap['detail'][$akun] += $nilai;
                    $lap['total'] += $nilai;
                }
            }
        }

        /* =====================================================
         | OUTPUT ROW
         ===================================================== */
        $rows = [];

        $rows[] = ['ASET', '', ''];
        foreach ($asetData as $kelompok => $data) {
            foreach ($data['detail'] as $akun => $val) {
                $rows[] = [$akun, $kelompok, $val];
            }
            $rows[] = ['Total ' . $kelompok, '', $data['total']];
            $rows[] = [];
        }

        $rows[] = ['LIABILITAS', '', ''];
        foreach ($liabilitasData as $kelompok => $data) {
            foreach ($data['detail'] as $akun => $val) {
                $rows[] = [$akun, $kelompok, $val];
            }
            $rows[] = ['Total ' . $kelompok, '', $data['total']];
            $rows[] = [];
        }

        $totalAset = array_sum(array_column($asetData, 'total'));
        $totalLiabilitas = array_sum(array_column($liabilitasData, 'total'));

        $rows[] = ['TOTAL ASET', '', $totalAset];
        $rows[] = ['TOTAL LIABILITAS', '', $totalLiabilitas];
        $rows[] = ['MODAL', '', $totalAset - $totalLiabilitas];

        return $rows;
    }

    public function title(): string
    {
        return 'Laporan Aset';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setItalic(true);
        $sheet->getStyle('A4:C4')->getFont()->setBold(true);
    }
}
