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

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';
    public bool $drawer = false;

    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    public int $user_id = 0;
    public int $kategori_id = 0;
    public int $client_id = 0;

    public int $filter = 0;
    public array $page = [
        ['id' => 10, 'name' => '10'],
        ['id' => 25, 'name' => '25'],
        ['id' => 50, 'name' => '50'],
        ['id' => 100, 'name' => '100']
    ];

    public int $perPage = 10;

    public string $startDate;
    public string $endDate;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function clear(): void
    {
        $this->reset(['search', 'user_id', 'kategori_id', 'client_id']);
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->resetPage();
        $this->success('Filters cleared.', position: 'toast-top');
    }

    public function delete($id): void
    {
        $transaksi = Transaksi::findOrFail($id);
        $transaksi->delete();

        DetailTransaksi::where('transaksi_id', $id)->delete();

        $this->warning("Transaksi $transaksi->name akan dihapus", position: 'toast-top');
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'invoice', 'label' => 'Invoice', 'class' => 'w-36'],
            ['key' => 'name', 'label' => 'Rincian', 'class' => 'w-56'],
            ['key' => 'tanggal', 'label' => 'Tanggal', 'class' => 'w-24'],
            ['key' => 'total', 'label' => 'Total', 'class' => 'w-24'],
            ['key' => 'user.name', 'label' => 'User', 'class' => 'w-24']
        ];
    }

    public function transaksis(): LengthAwarePaginator
    {
        return Transaksi::query()
            ->with(['client:id,name', 'details.kategori:id,name,type'])
            ->when($this->search, function (Builder $q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                          ->orWhere('invoice', 'like', "%{$this->search}%");
                });
            })
            ->when($this->user_id, fn(Builder $q) => $q->where('user_id', $this->user_id))
            ->when($this->client_id, fn(Builder $q) => $q->where('client_id', $this->client_id))
            ->when($this->kategori_id, function (Builder $q) {
                $q->whereHas('details', function ($query) {
                    $query->where('kategori_id', $this->kategori_id);
                });
            })
            ->when($this->startDate && $this->endDate, function (Builder $q) {
                $q->whereBetween('tanggal', [$this->startDate, $this->endDate]);
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        if ($this->filter >= 0 && $this->filter < 5) {
            $this->filter = 0;
            if (!empty($this->search)) $this->filter++;
            if ($this->user_id != 0) $this->filter++;
            if ($this->client_id != 0) $this->filter++;
            if ($this->kategori_id != 0) $this->filter++;
            if ($this->startDate || $this->endDate) $this->filter++;
        }

        return [
            'transaksis' => $this->transaksis(),
            'users' => User::all(['id', 'name']),
            'clients' => Client::all(['id', 'name']),
            'kategoris' => Kategori::all(['id', 'name']),
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
    <!-- HEADER -->
    <x-header title="Daftar Transaksi" separator progress-indicator />

    <!-- FILTERS -->
    <div class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end mb-4" >
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
        <x-table :headers="$headers" :rows="$transaksis" :sort-by="$sortBy" with-pagination link="telur-masuk/{id}/show?invoice={invoice}"/>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-input placeholder="Cari Nama / Invoice..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />
            <x-select placeholder="Pilih User" wire:model.live="user_id" :options="$users" icon="o-user"
                placeholder-value="0" />
            <x-select placeholder="Pilih Client" wire:model.live="client_id" :options="$clients" icon="o-user"
                placeholder-value="0" />
            <x-select placeholder="Pilih Kategori" wire:model.live="kategori_id" :options="$kategoris" icon="o-tag"
                placeholder-value="0" />

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
