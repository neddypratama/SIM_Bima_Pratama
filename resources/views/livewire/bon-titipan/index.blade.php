<?php

use App\Models\Client;
use App\Models\Transaksi;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exports\ClientExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB; // ✅ Pastikan ini di-import

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    public $tipeClientOptions = [['id' => 'Karyawan', 'name' => 'Karyawan'], ['id' => 'Peternak', 'name' => 'Peternak'], ['id' => 'Pedagang', 'name' => 'Pedagang'], ['id' => 'Supplier', 'name' => 'Supplier']];

    public ?string $tipeClient = null;

    public $tipePeternakOptions = [['id' => 'Elf', 'name' => 'Elf'], ['id' => 'Kuning', 'name' => 'Kuning'], ['id' => 'Merah', 'name' => 'Merah'], ['id' => 'Rumah', 'name' => 'Rumah']];
    public ?string $tipePeternak = null;

    public int $filter = 0;
    public $page = [['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100'], ['id' => 500, 'name' => '500']];
    public int $perPage = 25;
    public float $curah = 0;

    public function clear(): void
    {
        $this->reset(['search', 'tipeClient', 'tipePeternak', 'filter']);
        $this->resetPage();
        $this->success('Filters cleared.', position: 'toast-top');
    }

    public function export(): mixed
    {
        $this->success('Export dimulai...', position: 'toast-top');
        return Excel::download(new ClientExport(), 'client.xlsx');
    }

    public function delete($id): void
    {
        $client = Client::findOrFail($id);
        $client->delete();
        $this->warning("Client $client->name dihapus", position: 'toast-top');
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'type', 'label' => 'Tipe Client', 'class' => 'w-48'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-48'],
            ['key' => 'alamat', 'label' => 'Alamat', 'sortable' => false, 'class' => 'w-64'],
            ['key' => 'keterangan', 'label' => 'Keterangan', 'sortable' => false, 'class' => 'w-24'],
            ['key' => 'bon', 'label' => 'Bon', 'class' => 'w-36'], // Key disesuaikan
            ['key' => 'titipan', 'label' => 'Titipan', 'class' => 'w-36'], // Key disesuaikan
            ['key' => 'sisa', 'label' => 'Sisa', 'class' => 'w-36'],
        ];
    }

    public function clients(): LengthAwarePaginator
    {
        return Client::query()

            /* ================= PIUTANG ================= */
            ->withSum(
                [
                    'transaksi as piutang_debit' => function ($q) {
                        $q->where('type', 'Debit')
                            ->whereHas('details.kategori.detailKategori', function ($q) {
                                $q->where('type', 'Aset');
                            })
                            ->whereHas('details.kategori', function (Builder $q) {
                                $q->where('name', 'not like', '%Stok%')->where('name', 'not like', '%Kas%')->where('name', 'not like', '%Bank%');
                            });
                    },
                ],
                'total',
            )

            ->withSum(
                [
                    'transaksi as piutang_kredit' => function ($q) {
                        $q->where('type', 'Kredit')
                            ->whereHas('details.kategori.detailKategori', function ($q) {
                                $q->where('type', 'Aset');
                            })
                            ->whereHas('details.kategori', function (Builder $q) {
                                $q->where('name', 'not like', '%Stok%')->where('name', 'not like', '%Kas%')->where('name', 'not like', '%Bank%');
                            });
                    },
                ],
                'total',
            )

            /* ================= HUTANG ================= */
            ->withSum(
                [
                    'transaksi as hutang_kredit' => function ($q) {
                        $q->where('type', 'Kredit')->whereHas('details.kategori.detailKategori', function ($q) {
                            $q->where('type', 'Liabilitas');
                        });
                    },
                ],
                'total',
            )

            ->withSum(
                [
                    'transaksi as hutang_debit' => function ($q) {
                        $q->where('type', 'Debit')->whereHas('details.kategori.detailKategori', function ($q) {
                            $q->where('type', 'Liabilitas');
                        });
                    },
                ],
                'total',
            )

            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->tipeClient, fn($q) => $q->where('type', $this->tipeClient))
            ->when($this->tipePeternak, fn($q) => $q->where('keterangan', $this->tipePeternak))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        // Logika hitung filter aktif
        $count = 0;
        if ($this->search) {
            $count++;
        }
        if ($this->tipeClient) {
            $count++;
        }
        if ($this->tipePeternak) {
            $count++;
        }
        $this->filter = $count;
        
        $curah =
            DB::table('detail_transaksis as td')
                ->join('kategoris as k', 'k.id', '=', 'td.kategori_id')
                ->join('transaksis as t', 't.id', '=', 'td.transaksi_id')
                ->where('k.name', 'Penjualan Pakan Curah')
                ->selectRaw(
                    "
            SUM(
                CASE 
                    WHEN LOWER(t.type) = 'kredit' THEN td.sub_total
                    WHEN LOWER(t.type) = 'debit' THEN -td.sub_total
                    ELSE 0
                END
            ) as total_pendapatan
        ",
                )
                ->value('total_pendapatan') ?? 0;

        // ✅ HPP (SUDAH BENAR)
        $hpp =
            DB::table('detail_transaksis as td')
                ->join('kategoris as k', 'k.id', '=', 'td.kategori_id')
                ->join('transaksis as t', 't.id', '=', 'td.transaksi_id')
                ->join('barangs as b', 'b.id', '=', 'td.barang_id')
                ->join('jenis_barangs as jb', 'jb.id', '=', 'b.jenis_id')
                ->where('k.name', 'HPP')
                ->where('jb.name', 'Pakan Curah')
                ->selectRaw(
                    "
            SUM(
                CASE 
                    WHEN LOWER(t.type) = 'debit' THEN td.sub_total
                    WHEN LOWER(t.type) = 'kredit' THEN -td.sub_total
                    ELSE 0
                END
            ) as total_hpp
        ",
                )
                ->value('total_hpp') ?? 0;

        $this->curah = $curah - $hpp;
        // dd($this->curah, $this->hpp, $this->curah - $this->hpp);

        return [
            'clients' => $this->clients(),
            'headers' => $this->headers(),
        ];
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'tipeClient', 'tipePeternak', 'perPage'])) {
            $this->resetPage();
        }
    }
};

?>

<div>
    <x-header title="Daftar Klien" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Export" icon="o-arrow-down-tray" wire:click="export" class="btn-outline" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end mb-4">
        <div class="md:col-span-1">
            <x-select label="Show" :options="$page" wire:model.live="perPage" />
        </div>
        <div class="md:col-span-6">
            <x-input placeholder="Cari nama..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </div>
        <div class="md:col-span-1">
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel"
                badge="{{ $filter }}" badge-classes="badge-primary" />
        </div>
    </div>

    <x-card>
        <x-table :headers="$headers" :rows="$clients" :sort-by="$sortBy" with-pagination>

            {{-- Kolom Bon --}}
            @scope('cell_bon', $client)
                <span class="font-bold text-blue-600">
                    Rp
                    {{ number_format($piutang = ($client->piutang_debit ?? 0) - ($client->piutang_kredit ?? 0), 0, ',', '.') }}
                </span>
            @endscope

            {{-- Kolom Titipan --}}
            @scope('cell_titipan', $client)
                @if ($client->name == 'Bp.Supriyadi')
                    <span class="font-bold text-green-600">
                        Rp
                        {{ number_format($hutang = ($client->hutang_kredit ?? 0) - ($client->hutang_debit ?? 0) + ($this->curah ?? 0), 0, ',', '.') }}
                    </span>
                @else
                    <span class="font-bold text-green-600">
                        Rp
                        {{ number_format($hutang = ($client->hutang_kredit ?? 0) - ($client->hutang_debit ?? 0), 0, ',', '.') }}
                    </span>
                @endif
            @endscope

            {{-- Kolom Sisa (Bon - Titipan) --}}
            @scope('cell_sisa', $client)
                @php
                    $bon = ($client->piutang_debit ?? 0) - ($client->piutang_kredit ?? 0);
                    if ($client->name == 'Bp.Supriyadi') {
                        $titipan = ($client->hutang_kredit ?? 0) - ($client->hutang_debit ?? 0) + ($this->curah ?? 0);
                    } else {
                        $titipan = ($client->hutang_kredit ?? 0) - ($client->hutang_debit ?? 0);
                    }

                    $sisa = $bon - $titipan;
                    $warna = $sisa > 0 ? 'text-green-600' : ($sisa < 0 ? 'text-yellow-600' : 'text-gray-600');
                @endphp
                <span class="font-bold {{ $warna }}">
                    Rp {{ number_format($sisa, 0, ',', '.') }}
                </span>
            @endscope

            {{-- Aksi --}}
            @scope('actions', $client)
                <x-button icon="o-trash" wire:click="delete({{ $client->id }})" wire:confirm="Yakin ingin menghapus?"
                    spinner class="btn-ghost btn-sm text-red-500" />
            @endscope

        </x-table>
    </x-card>

    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-select label="Tipe Client" wire:model.live="tipeClient" :options="$tipeClientOptions" icon="o-flag"
                placeholder="Semua Tipe" />
            <x-select label="Grup Peternak" wire:model.live="tipePeternak" :options="$tipePeternakOptions" icon="o-tag"
                placeholder="Semua Grup" />
        </div>
        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer=false" />
        </x-slot:actions>
    </x-drawer>
</div>
