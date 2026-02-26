<?php

use App\Models\StokBatch;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

new class extends Component {
    use WithPagination, Toast;

    public string $search = '';
    public string $startDate = '';
    public string $endDate = '';

    public int $perPage = 25;

    public array $pages = [['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100'], ['id' => 500, 'name' => '500']];

    public function clear(): void
    {
        $this->reset(['search', 'startDate', 'endDate']);
        $this->resetPage();
        $this->success('Filter berhasil dibersihkan');
    }

    public function headers(): array
    {
        return [['key' => 'nama_barang', 'label' => 'Nama Barang'], ['key' => 'stok_beli', 'label' => 'Stok Beli'], ['key' => 'stok_jual', 'label' => 'Stok Jual'], ['key' => 'stok_tabel', 'label' => 'Stok (Tabel)'], ['key' => 'stok_sisa', 'label' => 'Stok Sisa'], ['key' => 'total_stok_batch', 'label' => 'Total Batch'], ['key' => 'aksi', 'label' => 'Aksi']];
    }

    public function perbandinganStokTelur(): LengthAwarePaginator
    {
        $stokBatchSub = DB::table('stok_batches')->select('barang_id', DB::raw('CAST(SUM(qty_sisa) AS DECIMAL(15,2)) as total_batch'))->groupBy('barang_id');

        $stokSub = DB::table('stoks')
            ->select(
                'barang_id',
                DB::raw('
                    CAST(
                        SUM(
                            COALESCE(tambah,0)
                            - COALESCE(kurang,0)
                            - (
                                COALESCE(kotor,0)
                                + COALESCE(bentes,0)
                                + COALESCE(ceplok,0)
                                + COALESCE(rusak,0)
                                + COALESCE(jumbo,0)
                            )
                        )
                    AS DECIMAL(15,2)) as stok_akhir
                '),
            )
            ->groupBy('barang_id');

        return DB::table('barangs as barang')
            ->select(
                'barang.id as id',
                'barang.name as nama_barang',

                DB::raw("
                    CAST(SUM(
                        CASE
                            WHEN transaksi.type = 'Debit'
                            AND kategori.name LIKE '%Stok%'
                            THEN detail_transaksis.kuantitas
                            ELSE 0
                        END
                    ) AS DECIMAL(15,2)) as stok_beli
                "),

                DB::raw("
                    CAST(SUM(
                        CASE
                            WHEN transaksi.type = 'Kredit'
                            AND kategori.name LIKE '%Penjualan%'
                            THEN detail_transaksis.kuantitas
                            ELSE 0
                        END
                    ) AS DECIMAL(15,2)) as stok_jual
                "),

                DB::raw('COALESCE(batch.total_batch, 0.00) as total_stok_batch'),
                DB::raw('COALESCE(stok.stok_akhir, 0.00) as stok_tabel'),
            )
            ->leftJoin('detail_transaksis', 'barang.id', '=', 'detail_transaksis.barang_id')
            ->leftJoin('transaksis as transaksi', 'detail_transaksis.transaksi_id', '=', 'transaksi.id')
            ->leftJoin('kategoris as kategori', 'kategori.id', '=', 'detail_transaksis.kategori_id')
            ->leftJoinSub($stokBatchSub, 'batch', 'batch.barang_id', '=', 'barang.id')
            ->leftJoinSub($stokSub, 'stok', 'stok.barang_id', '=', 'barang.id')

            ->when($this->search, fn($q) => $q->where('barang.name', 'like', "%{$this->search}%"))
            ->when($this->startDate, fn($q) => $q->whereDate('transaksi.tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('transaksi.tanggal', '<=', $this->endDate))

            ->groupBy('barang.id', 'barang.name', 'batch.total_batch', 'stok.stok_akhir')
            ->orderBy('barang.name')
            ->paginate($this->perPage);
    }

    public function perbaikiStokBatch(int $barangId): void
    {
        DB::transaction(function () use ($barangId) {
            /*
        |--------------------------------------------------------------------------
        | 1. HITUNG TOTAL STOK JUAL
        |--------------------------------------------------------------------------
        */
            $stokJual = DB::table('detail_transaksis as dt')->join('transaksis as t', 'dt.transaksi_id', '=', 't.id')->join('kategoris as k', 'dt.kategori_id', '=', 'k.id')->where('dt.barang_id', $barangId)->where('t.type', 'Kredit')->where('k.name', 'like', '%Penjualan%')->sum('dt.kuantitas');

            /*
        |--------------------------------------------------------------------------
        | 2. HITUNG STOK TABEL (REAL: TAMBAH - KURANG - RUSAK)
        |--------------------------------------------------------------------------
        */
            $stokTabel = DB::table('stoks')
                ->where('barang_id', $barangId)
                ->selectRaw(
                    '
                SUM(
                    COALESCE(tambah,0)
                    - COALESCE(kurang,0)
                    - (
                        COALESCE(kotor,0)
                        + COALESCE(bentes,0)
                        + COALESCE(ceplok,0)
                        + COALESCE(rusak,0)
                        + COALESCE(jumbo,0)
                    )
                )
            ',
                )
                ->value(
                    DB::raw('COALESCE(SUM(
                COALESCE(tambah,0)
                - COALESCE(kurang,0)
                - (
                    COALESCE(kotor,0)
                    + COALESCE(bentes,0)
                    + COALESCE(ceplok,0)
                    + COALESCE(rusak,0)
                    + COALESCE(jumbo,0)
                )
            ),0)'),
                );

            /*
                |--------------------------------------------------------------------------
                | 3. TOTAL STOK HABIS
                |--------------------------------------------------------------------------
                */
            $totalHabis = (float) $stokJual - (float) $stokTabel;

            // dd($stokJual, $stokTabel, $totalHabis);

            /*
        |--------------------------------------------------------------------------
        | 4. NORMALISASI BATCH
        | qty_sisa = qty_masuk
        |--------------------------------------------------------------------------
        */
            DB::table('stok_batches')
                ->where('barang_id', $barangId)
                ->lockForUpdate()
                ->update([
                    'qty_sisa' => DB::raw('qty_masuk'),
                    'updated_at' => now(),
                ]);

            /*
        |--------------------------------------------------------------------------
        | 5. KURANGI FIFO SESUAI TOTAL HABIS
        |--------------------------------------------------------------------------
        */
            $batches = StokBatch::where('barang_id', $barangId)->orderBy('tanggal')->lockForUpdate()->get();
            // dd($batches->sum('qty_masuk'));

            foreach ($batches as $batch) {
                if ($totalHabis <= 0) {
                    break;
                }

                $ambil = min($batch->qty_sisa, $totalHabis);

                $batch->decrement('qty_sisa', $ambil);

                $totalHabis -= $ambil;

                // dd($ambil, $totalHabis, $batch->fresh()->qty_sisa, $batch->fresh()->qty_masuk,);
            }

            /*
        |--------------------------------------------------------------------------
        | 6. JIKA MASIH HABIS (DATA JUAL > BATCH)
        | TAMBAHKAN BATCH BARU (KOREKSI)
        |--------------------------------------------------------------------------
        */
            if ($totalHabis < 0) {
                $lastBatch = DB::table('stok_batches')->where('barang_id', $barangId)->orderByDesc('tanggal')->first();

                DB::table('stok_batches')->insert([
                    'barang_id' => $barangId,
                    'user_id' => auth()->user()->id,
                    'qty_masuk' => abs($totalHabis),
                    'qty_sisa' => 0,
                    'harga' => $lastBatch?->harga ?? 0,
                    'tanggal' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->success('Stok batch berhasil diperbaiki & disinkronkan');
    }

    public function with(): array
    {
        return [
            'stokTelur' => $this->perbandinganStokTelur(),
            'headers' => $this->headers(),
            'pages' => $this->pages,
        ];
    }

    public function updated(): void
    {
        $this->resetPage();
    }
};
?>

<div>
    <x-header title="Perbandingan Stok Telur" separator progress-indicator />

    <x-card>
        <x-table :headers="$headers" :rows="$stokTelur" with-pagination>
            @scope('cell_stok_beli', $row)
                <span class="text-green-600 font-bold">
                    {{ number_format($row->stok_beli, 2, ',', '.') }}
                </span>
            @endscope

            @scope('cell_stok_jual', $row)
                <span class="text-red-600 font-bold">
                    {{ number_format($row->stok_jual, 2, ',', '.') }}
                </span>
            @endscope

            @scope('cell_total_stok_batch', $row)
                <span class="text-blue-600 font-bold">
                    {{ number_format($row->total_stok_batch, 2, ',', '.') }}
                </span>
            @endscope

            @scope('cell_stok_tabel', $row)
                <span class="text-purple-600 font-bold">
                    {{ number_format($row->stok_tabel, 2, ',', '.') }}
                </span>
            @endscope

            @scope('cell_stok_sisa', $row)
                <span class="text-yellow-600 font-bold">
                    {{ number_format($row->stok_beli - ($row->stok_jual - $row->stok_tabel), 2, ',', '.') }}
                </span>
            @endscope

            @scope('cell_aksi', $row)
                @php
                    $habis = $row->stok_beli - ($row->stok_jual - $row->stok_tabel);
                    $selisih = round($row->total_stok_batch - $habis, 2);
                @endphp

                @if ($selisih != 0)
                    <x-button label="Perbaiki" icon="o-wrench" class="btn-warning btn-sm"
                        wire:click="perbaikiStokBatch({{ $row->id }})" />
                @else
                    <span class="text-green-600 font-semibold">✔ Sinkron</span>
                @endif
            @endscope
        </x-table>
    </x-card>
</div>
