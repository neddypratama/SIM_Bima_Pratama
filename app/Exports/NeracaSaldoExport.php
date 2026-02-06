<?php

namespace App\Exports;

use App\Models\Transaksi;
use App\Models\Kategori;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NeracaSaldoExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected Carbon $startDate;
    protected Carbon $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $first = Transaksi::orderBy('tanggal')->first();
        $last  = Transaksi::orderByDesc('tanggal')->first();

        $this->startDate = $startDate
            ? Carbon::parse($startDate)->startOfDay()
            : Carbon::parse($first->tanggal)->startOfDay();

        $this->endDate = $endDate
            ? Carbon::parse($endDate)->endOfDay()
            : Carbon::parse($last->tanggal)->endOfDay();
    }

    public function array(): array
    {
        $rows = [];

        /* =============================
         | AMBIL TRANSAKSI
         ============================= */
        $details = Transaksi::with(['details.kategori.detailKategori'])
            ->whereBetween('tanggal', [$this->startDate, $this->endDate])
            ->whereHas('details', fn ($q) => $q->where('sub_total', '>', 0))
            ->get()
            ->flatMap(fn ($trx) =>
                $trx->details->map(fn ($d) => [
                    'kategori' => $d->kategori?->name,
                    'type' => $d->kategori?->detailKategori?->type,
                    'debit' => strtolower($trx->type) === 'debit' ? $d->sub_total : 0,
                    'kredit' => strtolower($trx->type) === 'kredit' ? $d->sub_total : 0,
                ])
            )
            ->filter(fn ($d) => $d['kategori']);

        /* =============================
         | TOTAL PER KATEGORI
         ============================= */
        $grouped = $details
            ->groupBy('kategori')
            ->map(fn ($items) => [
                'type' => $items->first()['type'],
                'debit' => $items->sum('debit'),
                'kredit' => $items->sum('kredit'),
            ]);

        /* =============================
         | SEMUA KATEGORI (TERMASUK 0)
         ============================= */
        $all = Kategori::with('detailKategori')->get()->map(function ($kat) use ($grouped) {
            $data = $grouped[$kat->name] ?? null;

            return [
                'kategori' => $kat->name,
                'type' => $kat->detailKategori?->type,
                'debit' => $data['debit'] ?? 0,
                'kredit' => $data['kredit'] ?? 0,
            ];
        });

        /* =============================
         | SUSUN SESUAI URUTAN NERACA
         ============================= */
        foreach (['Pendapatan', 'Pengeluaran', 'Aset', 'Liabilitas', 'Ekuitas'] as $section) {
            $rows[] = [$section, '', '', ''];

            foreach ($all->where('type', $section) as $row) {
                $rows[] = [
                    $row['kategori'],
                    $row['type'],
                    $row['debit'],
                    $row['kredit'],
                ];
            }

            $rows[] = ['', '', '', ''];
        }

        /* =============================
         | TOTAL AKHIR
         ============================= */
        $rows[] = [
            'TOTAL',
            '',
            $all->sum('debit'),
            $all->sum('kredit'),
        ];

        return $rows;
    }

    public function headings(): array
    {
        return ['Kategori / Akun', 'Tipe', 'Debit', 'Kredit'];
    }

    public function title(): string
    {
        return 'Neraca Saldo';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        $highest = $sheet->getHighestRow();
        $sheet->getStyle("C2:D{$highest}")
            ->getNumberFormat()
            ->setFormatCode('#,##0');

        $sheet->getStyle("A{$highest}:D{$highest}")
            ->getFont()->setBold(true);
    }
}
