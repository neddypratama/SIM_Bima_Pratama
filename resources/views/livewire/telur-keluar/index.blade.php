<?php

use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Barang;
use App\Models\User;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];
    public int $filter = 0;
    public int $perPage = 10;

    public $page = [['id' => 10, 'name' => '10'], ['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100']];

    public function clear(): void
    {
        $this->reset(['search', 'user_id', 'barang_id', 'filter']);
        $this->resetPage();
        $this->success('Filters cleared.', position: 'toast-top');
    }

    public function delete($id): void
    {
        // Ambil transaksi utama berdasarkan $id
        $transaksi = Transaksi::findOrFail($id);

        // Ambil HPP & Stok berdasarkan linked_id = transaksi utama
        $hpp = Transaksi::where('linked_id', $transaksi->id)->whereHas('kategori', fn($q) => $q->where('name', 'HPP'))->first();

        $stok = Transaksi::where('linked_id', $transaksi->id)->whereHas('kategori', fn($q) => $q->where('name', 'Stok Telur'))->first();

        // ✅ Kembalikan stok barang sebelum hapus detail stok
        if ($stok) {
            foreach ($stok->details as $detail) {
                $barang = Barang::find($detail->barang_id);
                if ($barang) {
                    $barang->increment('stok', $detail->kuantitas);
                }
            }

            $stok->details()->delete();
            $stok->delete();
        }

        if ($hpp) {
            $hpp->details()->delete();
            $hpp->delete();
        }

        $transaksi->details()->delete();
        $transaksi->delete();

        $this->warning("Transaksi {$transaksi->invoice}, relasi transaksi, dan semua detailnya berhasil dihapus & stok dikembalikan", position: 'toast-top');
    }

    public function headers(): array
    {
        return [['key' => 'invoice', 'label' => 'Invoice', 'class' => 'w-28'], ['key' => 'name', 'label' => 'Rincian', 'class' => 'w-44'], ['key' => 'tanggal', 'label' => 'Tanggal', 'class' => 'w-16'], ['key' => 'client.name', 'label' => 'Client', 'class' => 'w-16'], ['key' => 'kategori.name', 'label' => 'Kategori', 'class' => 'w-48'], ['key' => 'total', 'label' => 'Total', 'class' => 'w-32', 'format' => ['currency', 0, 'Rp']]];
    }

    public function transaksi(): LengthAwarePaginator
    {
        return Transaksi::query()
            ->with(['client:id,name', 'kategori:id,name,type'])
            ->where('type', 'Kredit')
            ->whereHas('kategori', function (Builder $q) {
                $q->where('name', 'like', 'Pendapatan Telur%');
            })
            ->when($this->search, fn(Builder $q) => $q->whereHas('transaksi', fn($t) => $t->where('invoice', 'like', "%{$this->search}%")))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        // $this->filter = ($this->search !== '' ? 1 : 0) + ($this->user_id !== 0 ? 1 : 0) + ($this->barang_id !== 0 ? 1 : 0);

        return [
            'transaksi' => $this->transaksi(),
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
    <x-header title="Transaksi Penjualan Telur" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Create" link="/telur-keluar/create" responsive icon="o-plus" class="btn-primary" />
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
        <x-table :headers="$headers" :rows="$transaksi" :sort-by="$sortBy" with-pagination
            link="telur-keluar/{id}/edit?invoice={invoice}">
            @scope('cell-kategori.name', $transaksi)
                {{ $transaksi->kategori?->name ?? '-' }}
            @endscope

            @scope('actions', $transaksi)
                <div class="flex">
                    <x-button icon="o-trash" wire:click="delete({{ $transaksi->id }})"
                        wire:confirm="Yakin ingin menghapus transaksi {{ $transaksi->invoice }} ini?" spinner
                        class="btn-ghost btn-sm text-red-500" />
                    <x-button icon="o-eye"
                        link="/telur-keluar/{{ $transaksi->id }}/show?invoice={{ $transaksi->invoice }}"
                        class="btn-ghost btn-sm text-yellow-500" />
                </div>
            @endscope
        </x-table>
    </x-card>

    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-input placeholder="Cari Invoice..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />

            {{-- ✅ Filter User --}}
            {{-- <x-select placeholder="Pilih User" wire:model.live="user_id" :options="$users" option-label="name"
                option-value="id" icon="o-user" placeholder-value="0" /> --}}

            {{-- ✅ Filter Barang --}}
            {{-- <x-select placeholder="Pilih Barang" wire:model.live="barang_id" :options="$barangs" option-label="name"
                option-value="id" icon="o-cube" placeholder-value="0" /> --}}
        </div>

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer=false" />
        </x-slot:actions>
    </x-drawer>
</div>
