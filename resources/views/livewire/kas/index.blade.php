<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

new class extends Component {
    use Toast, WithPagination;

    public string $search = '';
    public string $startDate = '';
    public string $endDate = '';
    public int $perPage = 10;

    public array $pages = [['id' => 10, 'name' => '10'], ['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100']];

    public function mount(): void
    {
        $today = Carbon::today()->toDateString();
        $this->startDate = $today;
        $this->endDate = $today;
    }

    public function headers(): array
    {
        return [['key' => 'kategori_kas', 'label' => 'Kategori Kas', 'class' => 'w-64'], ['key' => 'saldo_awal', 'label' => 'Saldo Awal (Rp)', 'class' => 'text-right w-44'], ['key' => 'pemasukan', 'label' => 'Pemasukan (Rp)', 'class' => 'text-right w-44'], ['key' => 'pengeluaran', 'label' => 'Pengeluaran (Rp)', 'class' => 'text-right w-44'], ['key' => 'saldo_akhir', 'label' => 'Saldo Akhir (Rp)', 'class' => 'text-right w-44']];
    }

    /** 🔹 Query laporan kas per kategori */
    public function laporanKas(): LengthAwarePaginator
    {
        $kategoriKas = DB::table('kategoris')
            ->where('type', 'Aset')
            ->where(function ($q) {
                $q->where('name', 'like', '%Kas%')->orWhere('name', 'like', '%Bank%');
            })
            ->pluck('id', 'name');

        // dd($kategoriKas);

        $data = collect();

        foreach ($kategoriKas as $nama => $id) {
            // Hitung tanggal kemarin dari startDate
            $kemarin = Carbon::parse($this->startDate)->subDay()->toDateString();

            // Saldo awal = total Debit - total Kredit pada tanggal kemarin saja
            $saldoAwalDebit = DB::table('transaksis as t')->join('detail_transaksis as d', 't.id', '=', 'd.transaksi_id')->where('d.kategori_id', $id)->where('t.type', 'Debit')->whereDate('t.tanggal', '=', $kemarin)->sum('t.total');

            $saldoAwalKredit = DB::table('transaksis as t')->join('detail_transaksis as d', 't.id', '=', 'd.transaksi_id')->where('d.kategori_id', $id)->where('t.type', 'Kredit')->whereDate('t.tanggal', '=', $kemarin)->sum('t.total');

            $saldoAwal = $saldoAwalDebit - $saldoAwalKredit;

            // Pemasukan hari ini (Debit)
            $pemasukan = DB::table('transaksis as t')
                ->join('detail_transaksis as d', 't.id', '=', 'd.transaksi_id')
                ->where('d.kategori_id', $id)
                ->where('t.type', 'Debit')
                ->when($this->search, fn($q) => $q->where('t.keterangan', 'like', "%{$this->search}%"))
                ->whereBetween('t.tanggal', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
                ->sum('t.total');

            // Pengeluaran hari ini (Kredit)
            $pengeluaran = DB::table('transaksis as t')
                ->join('detail_transaksis as d', 't.id', '=', 'd.transaksi_id')
                ->where('d.kategori_id', $id)
                ->where('t.type', 'Kredit')
                ->when($this->search, fn($q) => $q->where('t.keterangan', 'like', "%{$this->search}%"))
                ->whereBetween('t.tanggal', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
                ->sum('t.total');

            $saldoAkhir = $saldoAwal + $pemasukan - $pengeluaran;

            $data->push(
                (object) [
                    'kategori_kas' => $nama,
                    'saldo_awal' => $saldoAwal,
                    'pemasukan' => $pemasukan,
                    'pengeluaran' => $pengeluaran,
                    'saldo_akhir' => $saldoAkhir,
                ],
            );
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $items = $data->slice(($currentPage - 1) * $this->perPage, $this->perPage)->values();
        return new LengthAwarePaginator($items, $data->count(), $this->perPage, $currentPage);
    }

    public function with(): array
    {
        return [
            'laporanKas' => $this->laporanKas(),
            'headers' => $this->headers(),
            'pages' => $this->pages,
        ];
    }

    public function clear(): void
    {
        $today = Carbon::today()->toDateString();
        $this->reset(['search']);
        $this->startDate = $today;
        $this->endDate = $today;
        $this->resetPage();
        $this->success('Filter dibersihkan.', position: 'toast-top');
    }

    public function updated($property): void
    {
        if (!is_array($property)) {
            $this->resetPage();
        }
    }
};
?>

<div>
    <x-header title="Laporan Kas Harian" separator progress-indicator />

    <!-- 🔹 Filter -->
    <div class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end mb-4">
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
            <x-input placeholder="Cari keterangan..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />
        </div>
    </div>

    <!-- 🔹 Tabel Laporan -->
    <x-card>
        <x-table :headers="$headers" :rows="$laporanKas" with-pagination>
            @scope('cell_kategori_kas', $row)
                <span class="font-semibold">{{ $row->kategori_kas }}</span>
            @endscope

            @scope('cell_saldo_awal', $row)
                <div class="text-right text-blue-700">
                    Rp {{ number_format($row->saldo_awal, 2, ',', '.') }}
                </div>
            @endscope

            @scope('cell_pemasukan', $row)
                <div class="text-right text-green-600">
                    Rp {{ number_format($row->pemasukan, 2, ',', '.') }}
                </div>
            @endscope

            @scope('cell_pengeluaran', $row)
                <div class="text-right text-red-600">
                    Rp {{ number_format($row->pengeluaran, 2, ',', '.') }}
                </div>
            @endscope

            @scope('cell_saldo_akhir', $row)
                <div class="text-right text-purple-700 font-bold">
                    Rp {{ number_format($row->saldo_akhir, 2, ',', '.') }}
                </div>
            @endscope
        </x-table>
    </x-card>
</div>
