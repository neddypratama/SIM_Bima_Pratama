<?php

namespace App\Exports;

use App\Models\Barang;
use App\Models\StokBatch;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class BarangExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * Ambil data barang + stok + hpp dari stok batch
     */
    public function collection()
    {
        return Barang::query()
            ->with('jenis')

            // 🔹 STOK = SUM qty_sisa
            ->withSum('stokBatches as stok', 'qty_sisa')

            // 🔹 HPP = harga batch TERBARU
            ->selectSub(
                StokBatch::query()
                    ->select('harga')
                    ->whereColumn('stok_batches.barang_id', 'barangs.id')
                    ->orderByDesc('tanggal')
                    ->limit(1),
                'hpp'
            )

            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Heading Excel
     */
    public function headings(): array
    {
        return [
            'Nama',
            'Jenis Barang',
            'Stok',
            'HPP Terakhir',
            'Tanggal Dibuat',
        ];
    }

    /**
     * Mapping per baris
     */
    public function map($barang): array
    {
        return [
            $barang->name,
            $barang->jenis?->name ?? '-',
            (int) ($barang->stok ?? 0),
            (float) ($barang->hpp ?? 0),
            $barang->created_at?->format('Y-m-d'),
        ];
    }
}
