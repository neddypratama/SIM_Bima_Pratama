<?php

namespace App\Exports;

use App\Models\Transaksi;
use App\Models\Kategori;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class NeracaSaldoExport implements FromCollection, WithHeadings
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = Carbon::parse($startDate)->startOfDay();
        $this->endDate   = Carbon::parse($endDate)->endOfDay();
    }

    public function collection()
    {
        $kategoris = Kategori::all();
        $rows = collect();

        $grandDebit = 0;
        $grandKredit = 0;

        foreach ($kategoris as $kategori) {
            $transaksis = Transaksi::where('kategori_id', $kategori->id)
                ->whereBetween('tanggal', [$this->startDate, $this->endDate])
                ->get();

            $totalDebit  = $transaksis->where('type', 'Debit')->sum('total');
            $totalKredit = $transaksis->where('type', 'Kredit')->sum('total');

            $grandDebit  += $totalDebit;
            $grandKredit += $totalKredit;

            $rows->push([
                'Kategori' => $kategori->name,
                'Tipe' => $kategori->type,
                'Debit' => $totalDebit,
                'Kredit' => $totalKredit,
            ]);
        }

        // Tambahkan baris total di akhir
        $rows->push([
            'Kategori' => 'TOTAL',
            'Tipe' => '',
            'Debit' => $grandDebit,
            'Kredit' => $grandKredit,
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return ['Kategori', 'Tipe', 'Debit', 'Kredit'];
    }
}
