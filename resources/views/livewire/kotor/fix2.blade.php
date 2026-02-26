<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\DetailTransaksi;

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
        return [
            ['key' => 'nama_barang', 'label' => 'Nama Barang'],
            ['key' => 'stok_masuk', 'label' => 'Stok Masuk'],
            ['key' => 'stok_keluar', 'label' => 'Stok Keluar'],
            ['key' => 'sisa_stok', 'label' => 'Sisa Stok (Transaksi)'],
            ['key' => 'qty_sisa_batch', 'label' => 'Qty Sisa (Stok Batch)'],
            ['key' => 'status', 'label' => 'Status'], // ✅ tambahan
        ];
    }

    public function perbandinganStok(): LengthAwarePaginator
    {
        return DB::table('barangs as barang')
            ->select(
                'barang.id',
                'barang.name as nama_barang',

                // STOK MASUK
                DB::raw("
                    SUM(
                        CASE
                            WHEN transaksi.type = 'Debit'
                            AND kategori.name LIKE 'Stok%'
                            THEN detail_transaksis.kuantitas
                            ELSE 0
                        END
                    ) as stok_masuk
                "),

                // STOK KELUAR
                DB::raw("
                    SUM(
                        CASE
                            WHEN transaksi.type = 'Kredit'
                            AND kategori.name LIKE 'Stok%'
                            THEN detail_transaksis.kuantitas
                            ELSE 0
                        END
                    ) as stok_keluar
                "),

                // SISA TRANSAKSI
                DB::raw("
                    (
                        SUM(
                            CASE
                                WHEN transaksi.type = 'Debit'
                                AND kategori.name LIKE 'Stok%'
                                THEN detail_transaksis.kuantitas
                                ELSE 0
                            END
                        )
                        -
                        SUM(
                            CASE
                                WHEN transaksi.type = 'Kredit'
                                AND kategori.name LIKE 'Stok%'
                                THEN detail_transaksis.kuantitas
                                ELSE 0
                            END
                        )
                    ) as sisa_stok
                "),

                // QTY SISA BATCH
                DB::raw("
                    (
                        SELECT COALESCE(SUM(sb.qty_sisa),0)
                        FROM stok_batches sb
                        WHERE sb.barang_id = barang.id
                    ) as qty_sisa_batch
                "),

                // FINAL SISA (UNTUK STATUS)
                DB::raw("
                    (
                        (
                            SUM(
                                CASE
                                    WHEN transaksi.type = 'Debit'
                                    AND kategori.name LIKE 'Stok%'
                                    THEN detail_transaksis.kuantitas
                                    ELSE 0
                                END
                            )
                            -
                            SUM(
                                CASE
                                    WHEN transaksi.type = 'Kredit'
                                    AND kategori.name LIKE 'Stok%'
                                    THEN detail_transaksis.kuantitas
                                    ELSE 0
                                END
                            )
                        )
                        -
                        (
                            SELECT COALESCE(SUM(sb.qty_sisa),0)
                            FROM stok_batches sb
                            WHERE sb.barang_id = barang.id
                        )
                    ) as final_sisa
                "),
            )

            ->leftJoin('detail_transaksis', 'barang.id', '=', 'detail_transaksis.barang_id')
            ->leftJoin('transaksis as transaksi', 'detail_transaksis.transaksi_id', '=', 'transaksi.id')
            ->leftJoin('kategoris as kategori', 'kategori.id', '=', 'detail_transaksis.kategori_id')

            ->when($this->search, fn($q) => $q->where('barang.name', 'like', "%{$this->search}%"))
            ->when($this->startDate, fn($q) => $q->whereDate('transaksi.tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('transaksi.tanggal', '<=', $this->endDate))

            ->groupBy('barang.id', 'barang.name')
            ->orderBy('barang.name')
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        // dd(
        //     DetailTransaksi::with(['kategori', 'transaksi'])
        //         ->where('barang_id', 29)
        //         ->whereHas('kategori', function ($q) {
        //             $q->where('name', 'like', 'Stok%');
        //         })
        //         ->whereHas('transaksi', function ($q) {
        //             $q->where('type', 'Debit');
        //         })
        //         ->get(),
        // );

        return [
            'stokData' => $this->perbandinganStok(),
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
    <x-header title="Laporan Perbandingan Stok" separator progress-indicator />

    <div class="grid grid-cols-1 md:grid-cols-10 gap-4 items-end mb-4">
        <div class="md:col-span-2">
            <x-select label="Show entries" :options="$pages" wire:model.live="perPage" />
        </div>

        <div class="md:col-span-2">
            <x-input label="Tanggal Awal" type="date" wire:model.live="startDate" />
        </div>

        <div class="md:col-span-2">
            <x-input label="Tanggal Akhir" type="date" wire:model.live="endDate" />
        </div>

        <div class="md:col-span-2">
            <x-input placeholder="Cari nama barang..." wire:model.live.debounce="search" icon="o-magnifying-glass"
                clearable />
        </div>

        <div class="md:col-span-2">
            <x-button label="Reset" icon="o-x-mark" class="btn-outline" wire:click="clear" />
        </div>
    </div>

    <x-card>
        <x-table :headers="$headers" :rows="$stokData" with-pagination>

            @scope('cell_stok_masuk', $row)
                <span class="font-bold text-green-600">
                    {{ number_format($row->stok_masuk ?? 0, 2, ',', '.') }}
                </span>
            @endscope

            @scope('cell_stok_keluar', $row)
                <span class="font-bold text-red-600">
                    {{ number_format($row->stok_keluar ?? 0, 2, ',', '.') }}
                </span>
            @endscope

            @scope('cell_sisa_stok', $row)
                <span class="font-bold text-blue-600">
                    {{ number_format($row->sisa_stok ?? 0, 2, ',', '.') }}
                </span>
            @endscope

            @scope('cell_qty_sisa_batch', $row)
                <span class="font-bold text-purple-600">
                    {{ number_format($row->qty_sisa_batch ?? 0, 2, ',', '.') }}
                </span>
            @endscope

            {{-- STATUS --}}
            @scope('cell_status', $row)
                @php
                    $sisa = $row->final_sisa ?? 0;
                @endphp

                @if ($sisa < 0)
                    <span class="badge badge-error">Habis</span>
                @elseif ($sisa > 0)
                    <span class="badge badge-warning">Menipis</span>
                @else
                    <span class="badge badge-success">Aman</span>
                @endif
            @endscope

        </x-table>
    </x-card>
</div>
