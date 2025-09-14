<?php

use App\Models\DetailTransaksi;
use App\Models\User;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];
    public int $user_id = 0;
    public int $filter = 0;
    public int $perPage = 10;

    public $page = [
        ['id' => 10, 'name' => '10'],
        ['id' => 25, 'name' => '25'],
        ['id' => 50, 'name' => '50'],
        ['id' => 100, 'name' => '100']
    ];

    public function clear(): void
    {
        $this->reset(['search', 'user_id', 'filter']);
        $this->resetPage();
        $this->success('Filters cleared.', position: 'toast-top');
    }

    public function delete($id): void
    {
        $detail = DetailTransaksi::findOrFail($id);
        $detail->delete();
        $this->warning("Detail transaksi $id dihapus", position: 'toast-top');
    }

    public function headers(): array
    {
        return [
            ['key' => 'transaksi.invoice', 'label' => 'Invoice', 'class' => 'w-48'],
            ['key' => 'transaksi.tanggal', 'label' => 'Tanggal', 'class' => 'w-32'],
            ['key' => 'barang.name', 'label' => 'Barang', 'class' => 'w-64'],
            ['key' => 'kategori.name', 'label' => 'Kategori', 'class' => 'w-64'],
            ['key' => 'kuantitas', 'label' => 'Qty', 'class' => 'w-20 text-center'],
            ['key' => 'value', 'label' => 'Harga', 'class' => 'w-32 text-right'],
        ];
    }

    public function details(): LengthAwarePaginator
    {
        return DetailTransaksi::query()
            ->with(['transaksi:id,invoice,tanggal,user_id', 'barang:id,name', 'kategori:id,name'])
            ->whereHas('kategori', function (Builder $q) {
                $q->where('name', 'like', '%Telur%');
            })
            ->where('bagian', 'like', '%Aset%')
            ->when($this->search, fn (Builder $q) =>
                $q->whereHas('transaksi', fn ($t) =>
                    $t->where('invoice', 'like', "%{$this->search}%")
                )
            )
            ->when($this->user_id, fn (Builder $q) =>
                $q->whereHas('transaksi', fn ($t) =>
                    $t->where('user_id', $this->user_id)
                )
            )
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        $this->filter = ($this->search !== '' ? 1 : 0) + ($this->user_id !== 0 ? 1 : 0);

        return [
            'details' => $this->details(),
            'users' => User::all(),
            'headers' => $this->headers(),
            'perPage' => $this->perPage,
            'pages' => $this->page,
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
    <x-header title="Detail Transaksi" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Create" link="/details/create" responsive icon="o-plus" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end mb-4">
        <div class="md:col-span-1">
            <x-select label="Show entries" :options="$pages" wire:model.live="perPage" />
        </div>
        <div class="md:col-span-6">
            <x-input placeholder="Cari Invoice..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />
        </div>
        <div class="md:col-span-1">
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel"
                badge="{{ $this->filter }}" badge-classes="badge-primary" />
        </div>
    </div>

    <x-card>
        <x-table :headers="$headers" :rows="$details" :sort-by="$sortBy" with-pagination>
            @scope('cell-transaksi.invoice', $detail)
                {{ $detail->transaksi?->invoice ?? '-' }}
            @endscope

            @scope('cell-transaksi.tanggal', $detail)
                {{ $detail->transaksi?->tanggal ?? '-' }}
            @endscope

            @scope('cell-barang.name', $detail)
                {{ $detail->barang?->name ?? '-' }}
            @endscope

            @scope('cell-kategori.name', $detail)
                {{ $detail->kategori?->name ?? '-' }}
            @endscope

            @scope('cell-kuantitas', $detail)
                <div class="text-center">{{ $detail->kuantitas }}</div>
            @endscope

            @scope('cell-value', $detail)
                <div class="text-right">Rp {{ number_format($detail->value, 0, ',', '.') }}</div>
            @endscope

            @scope('actions', $detail)
                <x-button icon="o-trash" wire:click="delete({{ $detail['id'] }})"
                    wire:confirm="Yakin ingin menghapus detail transaksi ini?" spinner
                    class="btn-ghost btn-sm text-red-500" />
            @endscope
        </x-table>
    </x-card>

    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-input placeholder="Cari Invoice..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />
            <x-select placeholder="User" wire:model.live="user_id" :options="$users" icon="o-user"
                placeholder-value="0" />
        </div>

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer=false" />
        </x-slot:actions>
    </x-drawer>
</div>
