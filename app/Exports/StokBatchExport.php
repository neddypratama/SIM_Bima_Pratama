<?php

namespace App\Exports;

use App\Models\StokBatch;
use App\Models\Barang;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class StokBatchExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Ambil data transaksi + relasi
     */
    public function collection()
    {
        return StokBatch::query()
            ->with(['barang:id,name', 'user:id,name'])
            ->when($this->startDate, fn($q) => $q->whereDate('tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('tanggal', '<=', $this->endDate))
            ->orderBy('tanggal', 'asc')
            ->get();
    }

    /**
     * Atur heading kolom Excel
     */
    public function headings(): array
    {
        return [
            'Tanggal',
            'Pembuat',
            'Jenis Barang',
            'Barang',
            'HPP',
            'Stok',
            'Sisa',
        ];
    }

    /**
     * Atur data per row
     */
    public function map($stok): array
    {
        $rows = [
                $stok->tanggal,
                $stok->user->name,
                $stok->barang->jenis->name ?? '-',
                $stok->barang?->name ?? '-',
                $stok->harga ?? 0,
                $stok->qty_masuk ?? 0,
                $stok->qty_sisa ?? 0,
            ];

        return $rows;
    }
}
