<?php

use App\Models\Transaksi;
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

    // Create a public property.
    public int $user_id = 0;

    public int $filter = 0;

    public $page = [['id' => 10, 'name' => '10'], ['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100']];

    public int $perPage = 10; // Default jumlah data per halaman

    // Clear filters
    public function clear(): void
    {
        $this->reset();
        $this->resetPage();
        $this->success('Filters cleared.', position: 'toast-top');
    }

    // Delete action
    public function delete($id): void
    {
        $transaksi = Transaksi::findOrFail($id);
        $transaksi->delete();
        $this->warning("Transaksi $transaksi->name akan dihapus", position: 'toast-top');
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'invoice', 'label' => 'Invoice', 'class' => 'w-64'], ['key' => 'name', 'label' => 'Nama', 'class' => 'w-64'], ['key' => 'tanggal', 'label' => 'Tanggal', 'class' => 'w-64'], ['key' => 'total', 'label' => 'Total', 'class' => 'w-64'], ['key' => 'user.name', 'label' => 'User', 'class' => 'w-64'],
        ];
    }

    public function transaksis(): LengthAwarePaginator
    {
        return Transaksi::query()->withAggregate('user', 'name')->when($this->search, fn(Builder $q) => $q->where('name', 'like', "%$this->search%"))->when($this->user_id, fn(Builder $q) => $q->where('user_id', $this->user_id))->orderBy(...array_values($this->sortBy))->paginate($this->perPage);
    }

    public function with(): array
    {
        if ($this->filter >= 0 && $this->filter < 2) {
            if (!$this->search == null) {
                $this->filter = 1;
            } else {
                $this->filter = 0;
            }
            if (!$this->user_id == 0) {
                $this->filter += 1;
            }
        }
        return [
            'transaksis' => $this->transaksis(),
            'users' => User::all(),
            'headers' => $this->headers(),
            'perPage' => $this->perPage,
            'pages' => $this->page,
        ];
    }

    // Reset pagination when any component property changes
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
    <x-header title="Transaksis" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Create" link="/transaksis/create" responsive icon="o-plus" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <!-- FILTERS -->
    <div class="grid grid-cols-1 md:grid-cols-8 gap-4  items-end mb-4">
        <div class="md:col-span-1">
            <x-select label="Show entries" :options="$pages" wire:model.live="perPage" />
        </div>
        <div class="md:col-span-6">
            <x-input placeholder="Name..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass"
                class="" />
        </div>
        <div class="md:col-span-1">
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel"
                badge="{{ $this->filter }}" badge-classes="badge-primary" />
        </div>
        <!-- Dropdown untuk jumlah data per halaman -->
    </div>

    <!-- TABLE wire:poll.5s="Transaksis"  -->
    <x-card>
        <x-table :headers="$headers" :rows="$transaksis" :sort-by="$sortBy" with-pagination
            link="transaksis/{id}/edit?name={name}&user={user.name}">
            @scope('actions', $transaksi)
                <x-button icon="o-trash" wire:click="delete({{ $transaksi['id'] }})"
                    wire:confirm="Yakin ingin menghapus {{ $transaksi['name'] }}?" spinner
                    class="btn-ghost btn-sm text-red-500" />
            @endscope
        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-input placeholder="Name..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
            <x-select placeholder="User" wire:model.live="user_id" :options="$users" icon="o-user" placeholder-value="0" />
        </div>

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer=false" />
        </x-slot:actions>
    </x-drawer>
</div>
