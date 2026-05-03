<?php

use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\User;
use App\Models\Client;
use App\Models\Kategori;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use App\Exports\TransaksiExport;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';
    public bool $drawer = false;

    public array $sortBy = ['column' => 'tanggal', 'direction' => 'asc'];

    public ?string $validStatus = null; // 🔥 valid / invalid

    public int $filter = 0;
    public $pages = [['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100'], ['id' => 500, 'name' => '500']];

    public int $perPage = 25; // Default jumlah data per halaman

    public ?string $startDate = null;
    public ?string $endDate = null;

    public function clear(): void
    {
        $this->reset(['search', 'validStatus']);
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->resetPage();
        $this->success('Filters cleared.', position: 'toast-top');
    }

    public function headers(): array
    {
        return [['key' => 'kode', 'label' => 'Kode Invoice'], ['key' => 'tanggal', 'label' => 'Tanggal'], ['key' => 'total_debit', 'label' => 'Total Debit'], ['key' => 'total_kredit', 'label' => 'Total Kredit'], ['key' => 'status', 'label' => 'Status']];
    }

    public function transaksis(): LengthAwarePaginator
    {
        $data = DB::table('transaksis as t')
            ->join('detail_transaksis as d', 'd.transaksi_id', '=', 't.id')
            ->selectRaw(
            "
                SUBSTRING_INDEX(t.invoice, '-', -1) as kode,
                SUBSTRING_INDEX(SUBSTRING_INDEX(t.invoice, '-', 2), '-', -1) as tanggal_invoice,

                MIN(t.tanggal) as tanggal,

                SUM(CASE WHEN t.type = 'Debit' THEN d.sub_total ELSE 0 END) as total_debit,
                SUM(CASE WHEN t.type = 'Kredit' THEN d.sub_total ELSE 0 END) as total_kredit
            ",
            )
            ->groupBy('kode', 'tanggal_invoice');

        // 🔥 FILTER VALID / TIDAK
        if ($this->validStatus === 'valid') {
            $data->havingRaw('ABS(total_debit - total_kredit) < 0.01');
        } elseif ($this->validStatus === 'invalid') {
            $data->havingRaw('ABS(total_debit - total_kredit) >= 0.01');
        }

        $result = $data
        ->where('status', 'like', 'Selesai')
            ->when($this->startDate && $this->endDate, function (Builder $q) {
                $q->whereBetween('tanggal', [$this->startDate, $this->endDate]);
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);

        return $result;
    }

    public function with(): array
    {
        return [
            'transaksis' => $this->transaksis(),
            'headers' => $this->headers(), // 🔥 WAJIB
        ];
    }

    public function updated($property): void
    {
        if (!is_array($property) && $property != '') {
            $this->resetPage();
        }
    }
};
?>

<div>
    <!-- HEADER -->
    <x-header title="Daftar Transaksi" separator progress-indicator>
        <x-slot:actions>
            <div class="flex flex-row sm:flex-row gap-2">

            </div>
        </x-slot:actions>
    </x-header>

    <!-- FILTERS -->
    <div class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end mb-4">
        <div class="md:col-span-1">
            <x-select label="Show entries" :options="$pages" wire:model.live="perPage" />
        </div>
        <div class="md:col-span-6">
            <x-input placeholder="Cari Nama / Invoice..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />
        </div>
        <div class="md:col-span-1">
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel"
                badge="{{ $this->filter }}" badge-classes="badge-primary" />
        </div>
    </div>

    <x-card>
        <x-table :headers="$headers" :rows="$transaksis" :sort-by="$sortBy" with-pagination>

            {{-- KODE --}}
            @scope('cell_kode', $row)
                <b>{{ $row->kode }}</b>
            @endscope

            {{-- TANGGAL --}}
            @scope('cell_tanggal', $row)
                {{ \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y') }}
            @endscope

            {{-- TOTAL DEBIT --}}
            @scope('cell_total_debit', $row)
                Rp {{ number_format($row->total_debit, 0, ',', '.') }}
            @endscope

            {{-- TOTAL KREDIT --}}
            @scope('cell_total_kredit', $row)
                Rp {{ number_format($row->total_kredit, 0, ',', '.') }}
            @endscope

            {{-- STATUS --}}
            @scope('cell_status', $row)
                @php
                    $isValid = abs($row->total_debit - $row->total_kredit) < 0.01;
                @endphp

                @if ($isValid)
                    <span class="badge badge-success">Balance</span>
                @else
                    <span class="badge badge-error">Tidak Balance</span>
                @endif
            @endscope

        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-input placeholder="Cari Nama / Invoice..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />

            {{-- 🔥 FILTER VALID --}}
            <x-select label="Status" wire:model.live="validStatus" :options="[['id' => 'valid', 'name' => 'Balance'], ['id' => 'invalid', 'name' => 'Tidak Balance']]" placeholder="Semua" />
            <!-- ✅ Tambahan filter tanggal -->
            <x-input type="date" label="Dari Tanggal" wire:model.live="startDate" />
            <x-input type="date" label="Sampai Tanggal" wire:model.live="endDate" />
        </div>

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer=false" />
        </x-slot:actions>
    </x-drawer>
</div>
