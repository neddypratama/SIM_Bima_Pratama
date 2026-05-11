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
use Illuminate\Support\Facades\DB;

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';
    public bool $drawer = false;

    public array $sortBy = [
        'column' => 'tanggal',
        'direction' => 'asc',
    ];

    public ?string $validStatus = null;

    public int $filter = 0;

    public $pages = [['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100'], ['id' => 500, 'name' => '500']];

    public int $perPage = 25;

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
        return [['key' => 'kode', 'label' => 'Kode Invoice'], ['key' => 'tanggal', 'label' => 'Tanggal'], ['key' => 'total_debit', 'label' => 'Total Debit'], ['key' => 'total_kredit', 'label' => 'Total Kredit'], ['key' => 'selisih', 'label' => 'Selisih'], ['key' => 'status', 'label' => 'Status']];
    }

    public function transaksis(): LengthAwarePaginator
    {
        $data = DB::table('transaksis as t')
            ->join('detail_transaksis as d', 'd.transaksi_id', '=', 't.id')
            ->selectRaw(
                "
                SUBSTRING_INDEX(t.invoice, '-', -1) as kode,

                SUBSTRING_INDEX(
                    SUBSTRING_INDEX(t.invoice, '-', 2),
                    '-',
                    -1
                ) as tanggal_invoice,

                MIN(t.tanggal) as tanggal,

                SUM(
                    CASE
                        WHEN t.type = 'Debit'
                        THEN d.sub_total
                        ELSE 0
                    END
                ) as total_debit,

                SUM(
                    CASE
                        WHEN t.type = 'Kredit'
                        THEN d.sub_total
                        ELSE 0
                    END
                ) as total_kredit,

                ABS(
                    SUM(
                        CASE
                            WHEN t.type = 'Debit'
                            THEN d.sub_total
                            ELSE 0
                        END
                    ) -

                    SUM(
                        CASE
                            WHEN t.type = 'Kredit'
                            THEN d.sub_total
                            ELSE 0
                        END
                    )
                ) as selisih
            ",
            )
            ->where('t.status', 'Selesai')
            ->groupBy('kode', 'tanggal_invoice');

        // FILTER TANGGAL
        if ($this->startDate && $this->endDate) {
            $data->whereBetween('t.tanggal', [$this->startDate, $this->endDate]);
        }

        // FILTER STATUS
        if ($this->validStatus === 'valid') {
            $data->havingRaw('selisih <= 100');
        } elseif ($this->validStatus === 'invalid') {
            $data->havingRaw('selisih > 100');
        }

        return $data->orderBy($this->sortBy['column'], $this->sortBy['direction'])->paginate($this->perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO FIX BALANCE
    |--------------------------------------------------------------------------
    */
    public function fixBalance(): void
    {
        DB::transaction(function () {
            $datas = DB::table('transaksis as t')
                ->join('detail_transaksis as d', 'd.transaksi_id', '=', 't.id')
                ->selectRaw(
                    "
                    SUBSTRING_INDEX(t.invoice, '-', -1) as kode,

                    SUBSTRING_INDEX(
                        SUBSTRING_INDEX(t.invoice, '-', 2),
                        '-',
                        -1
                    ) as tanggal_invoice,

                    SUM(
                        CASE
                            WHEN t.type = 'Debit'
                            THEN d.sub_total
                            ELSE 0
                        END
                    ) as total_debit,

                    SUM(
                        CASE
                            WHEN t.type = 'Kredit'
                            THEN d.sub_total
                            ELSE 0
                        END
                    ) as total_kredit
                ",
                )
                ->where('t.status', 'Selesai')
                ->groupBy('kode', 'tanggal_invoice')
                ->get();

            foreach ($datas as $row) {
                $selisih = round($row->total_debit - $row->total_kredit, 0);

                // skip kalau balance
                if (abs($selisih) <= 100) {
                    continue;
                }

                // ambil transaksi terkait invoice
                $transaksis = Transaksi::query()
                    ->where('invoice', 'like', "%{$row->tanggal_invoice}%")
                    ->where('invoice', 'like', "%{$row->kode}")
                    ->get();

                if ($selisih > 0) {
                    /*
                    |--------------------------------------------------------------------------
                    | Debit lebih besar
                    | Tambahkan ke kredit
                    |--------------------------------------------------------------------------
                    */

                    $trx = $transaksis->where('type', 'Kredit')->first();

                    if (!$trx) {
                        continue;
                    }

                    $detail = DetailTransaksi::where('transaksi_id', $trx->id)->latest()->first();

                    if (!$detail) {
                        continue;
                    }

                    $detail->increment('sub_total', abs($selisih));

                    $trx->increment('total', abs($selisih));
                } else {
                    /*
                    |--------------------------------------------------------------------------
                    | Kredit lebih besar
                    | Tambahkan ke debit
                    |--------------------------------------------------------------------------
                    */

                    $trx = $transaksis->where('type', 'Debit')->first();

                    if (!$trx) {
                        continue;
                    }

                    $detail = DetailTransaksi::where('transaksi_id', $trx->id)->latest()->first();

                    if (!$detail) {
                        continue;
                    }

                    $detail->increment('sub_total', abs($selisih));

                    $trx->increment('total', abs($selisih));
                }
            }
        });

        $this->success('Berhasil memperbaiki transaksi tidak balance', position: 'toast-top');
    }

    public function with(): array
    {
        return [
            'transaksis' => $this->transaksis(),
            'headers' => $this->headers(),
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

            <div class="flex flex-row gap-2">

                <x-button label="Perbaiki Balance" icon="o-wrench-screwdriver" class="btn-warning" wire:click="fixBalance"
                    spinner />

            </div>

        </x-slot:actions>
    </x-header>

    <!-- FILTER -->
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

    <!-- TABLE -->
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

            {{-- DEBIT --}}
            @scope('cell_total_debit', $row)
                Rp {{ number_format($row->total_debit, 0, ',', '.') }}
            @endscope

            {{-- KREDIT --}}
            @scope('cell_total_kredit', $row)
                Rp {{ number_format($row->total_kredit, 0, ',', '.') }}
            @endscope

            {{-- SELISIH --}}
            @scope('cell_selisih', $row)
                Rp {{ number_format($row->selisih, 0, ',', '.') }}
            @endscope

            {{-- STATUS --}}
            @scope('cell_status', $row)
                @php
                    $isValid = $row->selisih <= 100;
                @endphp

                @if ($isValid)
                    <span class="badge badge-success">
                        Balance
                    </span>
                @else
                    <span class="badge badge-error">
                        Tidak Balance
                    </span>
                @endif
            @endscope

        </x-table>

    </x-card>

    <!-- DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">

        <div class="grid gap-5">

            <x-input placeholder="Cari Nama / Invoice..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />

            <x-select label="Status" wire:model.live="validStatus" :options="[['id' => 'valid', 'name' => 'Balance'], ['id' => 'invalid', 'name' => 'Tidak Balance']]" placeholder="Semua" />

            <x-input type="date" label="Dari Tanggal" wire:model.live="startDate" />

            <x-input type="date" label="Sampai Tanggal" wire:model.live="endDate" />

        </div>

        <x-slot:actions>

            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />

            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer=false" />

        </x-slot:actions>

    </x-drawer>

</div>
