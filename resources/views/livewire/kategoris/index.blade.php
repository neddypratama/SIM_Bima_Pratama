<?php

use App\Models\Kategori;
use App\Models\JenisBarang;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Rule;

new class extends Component {
    use Toast;
    use WithPagination;
    public string $search = '';

    public bool $drawer = false;

    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    public int $filter = 0;

    public $page = [['id' => 10, 'name' => '10'], ['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100']];

    public int $perPage = 10; // Default jumlah data per halaman

    public array $typeOptions = [['id' => 'Aset', 'name' => 'Aset'], ['id' => 'Liabilitas', 'name' => 'Liabilitas'], ['id' => 'Pendapatan', 'name' => 'Pendapatan'], ['id' => 'Pengeluaran', 'name' => 'Pengeluaran'], ['id' => 'Modal', 'name' => 'Modal']];

    public bool $editModal = false; // Untuk menampilkan modal edit
    public ?Kategori $editingKategori = null; // Menyimpan Kategori yang sedang diedit
    public string $editingName = '';
    public string $editingDeskripsi = ''; // Menyimpan nilai input untuk nama Kategori
    public string $editingType = '';

    public bool $createModal = false; // Untuk menampilkan modal create

    public string $newKategoriName = '';
    public string $newKategoriDeskripsi = ''; // Untuk menyimpan input nama Kategori baru
    public string $newKategoriType = '';

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
        $kategori = Kategori::findOrFail($id);
        $kategori->delete();
        $this->warning("Kategori $kategori->name akan dihapus", position: 'toast-top');
    }

    public function create(): void
    {
        $this->newKategoriName = ''; // Reset input sebelum membuka modal
        $this->newKategoriDeskripsi = '';
        $this->newKategoriType = '';
        $this->createModal = true;
    }

    public function saveCreate(): void
    {
        $this->validate([
            'newKategoriName' => 'required|string|max:255',
            'newKategoriDeskripsi' => 'nullable',
            'newKategoriType' => 'required|string|in:Aset,Liabilitas,Pendapatan,Pengeluaran,Modal',
        ]);

        Kategori::create(['name' => $this->newKategoriName, 'deskripsi' => $this->newKategoriDeskripsi, 'type' => $this->newKategoriType]);

        $this->createModal = false;
        $this->success('Kategori created successfully.', position: 'toast-top');
    }

    public function edit($id): void
    {
        if (Auth()->user()->role_id == 1) {
            $this->editingKategori = Kategori::find($id);

            if ($this->editingKategori) {
                $this->editingName = $this->editingKategori->name;
                $this->editingDeskripsi = $this->editingKategori->deskripsi;
                $this->editingType = $this->editingKategori->type;
                $this->editModal = true; // Tampilkan modal
            }
        } else {
            $this->warning('Mohon maaf tidak bisa edit kategori', position: 'toast-top');
        }
    }

    public function saveEdit(): void
    {
        if ($this->editingKategori) {
            $this->validate([
                'editingName' => 'required|string|max:255',
                'editingDeskripsi' => 'nullable',
                'editingType' => 'required|string|in:Aset,Liabilitas,Pendapatan,Pengeluaran,Modal',
            ]);
            $this->editingKategori->update(['name' => $this->editingName, 'deskripsi' => $this->editingDeskripsi, 'type' => $this->editingType, 'updated_at' => now()]);
            $this->editModal = false;
            $this->success('Kategori updated successfully.', position: 'toast-top');
        }
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-64'],
            ['key' => 'deskripsi', 'label' => 'Deskripsi', 'class' => 'w-100'],
            ['key' => 'type', 'label' => 'Type', 'class' => 'w-30'], // Gunakan `Transaksis_count`
            ['key' => 'created_at', 'label' => 'Tanggal dibuat', 'class' => 'w-30'],
        ];
    }

    public function kategoris(): LengthAwarePaginator
    {
        return Kategori::query()
            ->withAggregate('jenis', 'name')
            ->when($this->search, function (Builder $q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")->orWhere('type', 'like', "%{$this->search}%");
                });
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'kategoris' => $this->kategoris(),
            'typeOptions' => $this->typeOptions,
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
    <x-header title="Daftar Kategori" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Create" @click="$wire.create()" responsive icon="o-plus" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <!-- FILTERS -->
    <div class="grid grid-cols-1 md:grid-cols-8 gap-4  items-end mb-4">
        <div class="md:col-span-1">
            <x-select label="Show entries" :options="$pages" wire:model.live="perPage" class="w-15" />
        </div>
        <div class="md:col-span-7">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass"
                class="" />
        </div>
        <!-- Dropdown untuk jumlah data per halaman -->
    </div>

    <!-- TABLE wire:poll.5s="users"  -->
    <x-card>
        <x-table :headers="$headers" :rows="$kategoris" :sort-by="$sortBy" with-pagination
            @row-click="$wire.edit($event.detail.id)">
            @scope('actions', $kategoris)
                <x-button icon="o-trash" wire:click="delete({{ $kategoris['id'] }})"
                    wire:confirm="Yakin ingin menghapus {{ $kategoris['name'] }}?" spinner
                    class="btn-ghost btn-sm text-red-500" />
            @endscope
        </x-table>
    </x-card>

    <x-modal wire:model="createModal" title="Create Kategori">
        <div class="grid gap-4">
            <x-input label="Kategori Name" wire:model.live="newKategoriName" />
            <x-textarea label="Kategori Deskripsi" wire:model.live="newKategoriDeskripsi" placeholder="Here ..." />
            <x-select label="Kategori Type" wire:model.live="newKategoriType" :options="$typeOptions"
                placeholder="Pilih Type" />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" icon="o-x-mark" @click="$wire.createModal=false" />
            <x-button label="Create" icon="o-check" class="btn-primary" wire:click="saveCreate" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="editModal" title="Edit Kategori">
        <div class="grid gap-4">
            <x-input label="Kategori Name" wire:model.live="editingName" />
            <x-textarea label="Kategori Deskripsi" wire:model.live="editingDeskripsi" placeholder="Here ..." />
            <x-select label="Kategori Type" wire:model.live="editingType" :options="$typeOptions" placeholder="Pilih Type" />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" icon="o-x-mark" @click="$wire.editModal=false" />
            <x-button label="Save" icon="o-check" class="btn-primary" wire:click="saveEdit" />
        </x-slot:actions>
    </x-modal>
</div>
