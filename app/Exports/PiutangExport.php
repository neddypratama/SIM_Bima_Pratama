<?php

namespace App\Exports;

use App\Models\Transaksi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class PiutangExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
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
        return Transaksi::with(['client:id,name', 'kategori:id,name,type', 'user:id,name'])
            ->whereHas('kategori', fn($q) => $q->where('name', 'like', 'Piutang%'))
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
            'Invoice',
            'Rincian',
            'Tanggal',
            'Client',
            'Kategori',
            'Total',
            'Pembuat',
            'Tipe Transaksi'
        ];
    }

    /**
     * Atur data per row
     */
    public function map($transaksi): array
    {
        return [
            $transaksi->invoice,
            $transaksi->name,
            $transaksi->tanggal,
            $transaksi->client?->name ?? '-',
            $transaksi->kategori?->name ?? '-',
            $transaksi->total,
            $transaksi->user->name,
            $transaksi->type === 'Debit' ? 'Piutang Bertambah' : 'Piutang Berkurang',
        ];
    }
}
