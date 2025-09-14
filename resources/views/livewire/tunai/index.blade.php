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
    public int $user_id = 0; // ✅ filter user
    public int $barang_id = 0; // ✅ filter barang
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
        $details = DetailTransaksi::where('transaksi_id', $id)->get();

        // Kalau sudah yakin ada datanya, hapus dulu semua detail
        DetailTransaksi::where('transaksi_id', $id)->delete();

        // Baru hapus transaksi
        Transaksi::where('id', $id)->delete();

        $this->warning("Transaksi $id dan semua detailnya berhasil dihapus", position: 'toast-top');
    }

    public function headers(): array
    {
        return [
            ['key' => 'transaksi.invoice', 'label' => 'Invoice', 'class' => 'w-32'], 
            ['key' => 'transaksi.tanggal', 'label' => 'Tanggal', 'class' => 'w-32'], 
            ['key' => 'transaksi.client.name', 'label' => 'Client', 'class' => 'w-32'], 
            ['key' => 'barang.name', 'label' => 'Barang', 'class' => 'w-32'], 
            ['key' => 'kategori.name', 'label' => 'Kategori', 'class' => 'w-48'], 
            ['key' => 'kuantitas', 'label' => 'Qty', 'class' => 'w-1 text-center'], 
            ['key' => 'value', 'label' => 'Harga', 'class' => 'w-32 text-right', 'format' => ['currency',  0, 'Rp']], 
            ['key' => 'transaksi.total', 'label' => 'Total', 'class' => 'w-32', 'format' => ['currency',  0, 'Rp']],
        ];
    }

    public function details(): LengthAwarePaginator
    {
        return DetailTransaksi::query()
            ->with(['transaksi:id,invoice,tanggal,total,client_id', 'transaksi.client:id,name', 'barang:id,name', 'kategori:id,name'])
            ->whereHas('kategori', fn(Builder $q) => $q->where('name', 'like', '%Kas%'))
            ->where('bagian', 'like', '%Aset%')
            ->orWhere('bagian', 'like', '%Liabilitas%')
            ->when($this->search, fn(Builder $q) => $q->whereHas('transaksi', fn($t) => $t->where('invoice', 'like', "%{$this->search}%")))
            ->when($this->user_id, fn(Builder $q) => $q->whereHas('transaksi', fn($t) => $t->where('user_id', $this->user_id)))
            ->when($this->barang_id, fn(Builder $q) => $q->where('barang_id', $this->barang_id))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        $this->filter = ($this->search !== '' ? 1 : 0) + ($this->user_id !== 0 ? 1 : 0) + ($this->barang_id !== 0 ? 1 : 0);

        return [
            'details' => $this->details(),
            'barangs' => Barang::whereHas('jenis', fn($q) => $q->where('name', 'like', '%Telur%'))->select('id', 'name')->get(),
            'users' => User::select('id', 'name')->get(),
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
    <x-header title="Transaksi Kas Tunai" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Create" link="/tunai/create" responsive icon="o-plus" class="btn-primary" />
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
        <x-table :headers="$headers" :rows="$details" :sort-by="$sortBy" with-pagination
            link="tunai/{transaksi.id}/edit?barang={barang.name}&invoice={transaksi.invoice}">
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
            {{ $detail->kuantitas }}
            @endscope

            @scope('cell-value', $detail)
                {{ $detail->value ?? 0 }}.
            @endscope

            @scope('actions', $detail)
                <x-button icon="o-trash" wire:click="delete({{ $detail['transaksi.id'] }})"
                    wire:confirm="Yakin ingin menghapus detail transaksi ini?" spinner
                    class="btn-ghost btn-sm text-red-500" />
            @endscope
        </x-table>
    </x-card>

    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-input placeholder="Cari Invoice..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />

            {{-- ✅ Filter User --}}
            <x-select placeholder="Pilih User" wire:model.live="user_id" :options="$users" option-label="name"
                option-value="id" icon="o-user" placeholder-value="0" />

            {{-- ✅ Filter Barang --}}
            <x-select placeholder="Pilih Barang" wire:model.live="barang_id" :options="$barangs" option-label="name"
                option-value="id" icon="o-cube" placeholder-value="0" />
        </div>

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer=false" />
        </x-slot:actions>
    </x-drawer>
</div>
