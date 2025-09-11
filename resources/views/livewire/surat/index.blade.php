<?php

use App\Models\Arsip;
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

    public int $kategori_id = 0;

    public int $badge = 0;

    public string $search = '';

    public bool $drawer = false;

    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

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
        $arsip = Arsip::findOrFail($id);

        // Hapus file PDF jika ada
        if ($arsip->file && Storage::disk('public')->exists(str_replace('/storage/', '', $arsip->file))) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $arsip->file));
        }

        // Hapus record arsip
        $arsip->delete();

        $this->warning("Arsip #$id berhasil dihapus.", position: 'toast-bottom');
    }

    // Download action
    public function download($id)
    {
        $arsip = Arsip::findOrFail($id);

        if (!$arsip->file || !\Storage::disk('public')->exists(str_replace('/storage/', '', $arsip->file))) {
            $this->error('File tidak ditemukan.', position: 'toast-bottom');
            return;
        }

        return response()->download(storage_path('app/public/' . str_replace('/storage/', '', $arsip->file)), basename($arsip->file), ['Content-Type' => 'application/pdf']);
    }

    // Table headers
    public function headers(): array
    {
        return [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'no_surat', 'label' => 'No Surat', 'class' => 'w-64'], ['key' => 'kategori_name', 'label' => 'Kategori Surat'], ['key' => 'judul', 'label' => 'Judul Surat', 'class' => 'w-64'], ['key' => 'tanggal', 'label' => 'Waktu Pengarsipan', 'sortable' => false]];
    }

    public function arsip(): LengthAwarePaginator
    {
        return Arsip::query()
            ->withAggregate('kategori', 'name')
            ->when($this->search, function (Builder $q) {
                $q->where(function (Builder $q2) {
                    $q2->where('no_surat', 'like', "%{$this->search}%")->orWhere('judul', 'like', "%{$this->search}%");
                });
            })
            ->when($this->kategori_id, fn(Builder $q) => $q->where('kategori_id', $this->kategori_id))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(5);
    }

    public function with(): array
    {
        if ($this->kategori_id < 1) {
            $this->badge = 0;
        } else {
            $this->badge = 1;
        }

        return [
            'arsip' => $this->arsip(),
            'headers' => $this->headers(),
            'kategori' => Kategori::all(),
            'badge' => $this->badge,
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Arsip Surat" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" badge="{{ $this->badge }}"
                badge-classes="badge-primary" />
            <x-button label="Create" link="/surat/create" responsive icon="o-plus" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-card shadow>
        <x-table :headers="$headers" :rows="$arsip" :sort-by="$sortBy" with-pagination
            link="surat/{id}/edit?judul={judul}&kategori={kategori.name}">
            @scope('actions', $arsip)
                <div class="flex">
                    <x-button icon="o-trash" wire:click="delete({{ $arsip['id'] }})" wire:confirm="Are you sure?" spinner
                        class="btn-ghost btn-sm text-error" />
                    <x-button icon="fas.download" wire:click="download({{ $arsip['id'] }})" wire:confirm="Are you sure?"
                        spinner class="btn-ghost btn-sm text-success" />
                    <x-button icon="fas.eye" link="/surat/{{ $arsip['id'] }}" class="btn-ghost btn-sm text-warning" />
                @endscope=
        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-input placeholder="Search..." wire:model.live.debounce="search" icon="o-magnifying-glass"
            @keydown.enter="$wire.drawer = false" />
        <x-select placeholer="Kategori" wire:model.live="kategori_id" :options="$kategori" icon="o-flag"
            placeholder-value="0" />

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>

</div>
