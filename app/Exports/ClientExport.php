<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClientExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * Ambil data client + agregasi transaksi
     */
    public function collection()
    {
        return Client::query()

            /* ================= PIUTANG (BON) ================= */
            ->withSum(
                [
                    'transaksi as piutang_debit' => function ($q) {
                        $q->where('type', 'Debit')
                          ->whereHas('details.kategori', fn ($q) =>
                              $q->where('name', 'like', 'Piutang%')
                          );
                    },
                ],
                'total'
            )

            ->withSum(
                [
                    'transaksi as piutang_kredit' => function ($q) {
                        $q->where('type', 'Kredit')
                          ->whereHas('details.kategori', fn ($q) =>
                              $q->where('name', 'like', 'Piutang%')
                          );
                    },
                ],
                'total'
            )

            /* ================= HUTANG (TITIPAN) ================= */
            ->withSum(
                [
                    'transaksi as hutang_kredit' => function ($q) {
                        $q->where('type', 'Kredit')
                          ->whereHas('details.kategori', fn ($q) =>
                              $q->where('name', 'like', 'Hutang%')
                          );
                    },
                ],
                'total'
            )

            ->withSum(
                [
                    'transaksi as hutang_debit' => function ($q) {
                        $q->where('type', 'Debit')
                          ->whereHas('details.kategori', fn ($q) =>
                              $q->where('name', 'like', 'Hutang%')
                          );
                    },
                ],
                'total'
            )

            ->orderBy('name')
            ->get();
    }

    /**
     * Heading Excel
     */
    public function headings(): array
    {
        return [
            'Nama',
            'Tipe',
            'Alamat',
            'Keterangan',
            'Bon (Piutang)',
            'Titipan (Hutang)',
        ];
    }

    /**
     * Mapping per row
     */
    public function map($client): array
    {
        // BON = Debit - Kredit
        $bon = ($client->piutang_debit ?? 0) - ($client->piutang_kredit ?? 0);

        // TITIPAN = Kredit - Debit
        $titipan = ($client->hutang_kredit ?? 0) - ($client->hutang_debit ?? 0);

        return [
            $client->name,
            $client->type,
            $client->alamat,
            $client->keterangan,
            $bon,
            $titipan,
        ];
    }
}
