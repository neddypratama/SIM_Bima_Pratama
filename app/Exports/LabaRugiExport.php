<?php

namespace App\Exports;

use App\Models\Transaksi;
use App\Models\Kategori;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LabaRugiExport implements FromCollection, WithHeadings
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
        $kategoris = Kategori::whereIn('type', ['Pendapatan', 'Pengeluaran'])->get();

        $rows = collect();
        $totalPendapatan = 0;
        $totalPengeluaran = 0;
        $bebanPajak = 0;

        foreach ($kategoris as $kategori) {
            $total = Transaksi::where('kategori_id', $kategori->id)
                ->whereBetween('tanggal', [$this->startDate, $this->endDate])
                ->sum('total');

            if ($kategori->type === 'Pendapatan') {
                $totalPendapatan += $total;
                $rows->push([
                    'Kategori' => $kategori->name,
                    'Tipe' => 'Pendapatan',
                    'Jumlah' => $total,
                ]);
            } elseif ($kategori->type === 'Pengeluaran') {
                if (strtolower($kategori->name) === 'beban pajak') {
                    $bebanPajak += $total; // jangan masukkan ke totalPengeluaran
                } else {
                    $totalPengeluaran += $total;
                    $rows->push([
                        'Kategori' => $kategori->name,
                        'Tipe' => 'Pengeluaran',
                        'Jumlah' => $total,
                    ]);
                }
            }
        }

        // Hitung Laba Sebelum Pajak & Laba Bersih
        $labaSebelumPajak = $totalPendapatan - $totalPengeluaran;
        $labaBersih = $labaSebelumPajak - $bebanPajak;

        // Tambahkan ringkasan di akhir
        $rows->push(['Kategori' => 'TOTAL PENDAPATAN', 'Tipe' => '', 'Jumlah' => $totalPendapatan]);
        $rows->push(['Kategori' => 'TOTAL PENGELUARAN', 'Tipe' => '', 'Jumlah' => $totalPengeluaran]);
        $rows->push(['Kategori' => 'LABA SEBELUM PAJAK', 'Tipe' => '', 'Jumlah' => $labaSebelumPajak]);
        $rows->push(['Kategori' => 'BEBAN PAJAK', 'Tipe' => '', 'Jumlah' => $bebanPajak]);
        $rows->push(['Kategori' => 'LABA BERSIH', 'Tipe' => '', 'Jumlah' => $labaBersih]);

        return $rows;
    }

    public function headings(): array
    {
        return ['Kategori', 'Tipe', 'Jumlah'];
    }
}
