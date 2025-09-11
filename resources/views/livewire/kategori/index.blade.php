<?php

use App\Models\Kategori;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';

    public bool $drawer = false;

    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    public function updated($property): void
    {
        if (!is_array($property) && $property != '') {
            $this->resetPage();
        }
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetPage();
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Delete action
    public function delete($id): void
    {
        Kategori::find($id)->delete();
        $this->warning("Will delete #$id", 'It is fake.', position: 'toast-bottom');
    }

    // Table headers
    public function headers(): array
    {
        return [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'name', 'label' => 'Name', 'class' => 'w-64'], ['key' => 'keterangan', 'label' => 'Keterangan', 'sortable' => false]];
    }

    public function kategori(): LengthAwarePaginator
    {
        return Kategori::query()->when($this->search, fn(Builder $q) => $q->where('name', 'like', "%$this->search%"))->orderBy(...array_values($this->sortBy))->paginate(5);
    }

    public function with(): array
    {

        return [
            'kategori' => $this->kategori(),
            'headers' => $this->headers(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Kategori Surat" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            {{-- <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel"/> --}}
            <x-button label="Create" link="/kategori/create" responsive icon="o-plus" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-card shadow>
        <x-table :headers="$headers" :rows="$kategori" :sort-by="$sortBy" with-pagination
            link="kategori/{id}/edit?name={name}">
            @scope('actions', $kategori)
                <x-button icon="o-trash" wire:click="delete({{ $kategori['id'] }})" wire:confirm="Are you sure?" spinner
                    class="btn-ghost btn-sm text-error" />
            @endscope
        </x-table>
    </x-card>

    {{-- <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-input placeholder="Search..." wire:model.live.debounce="search" icon="o-magnifying-glass"
            @keydown.enter="$wire.drawer = false" />
        <x-select placeholer="Country" wire:model.live="country_id" :options="$countries" icon="o-flag"
            placeholder-value="0" />

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer> --}}
</div>
