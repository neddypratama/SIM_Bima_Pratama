<?php

use App\Models\Satuan;
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

    // Create a public property.
    // public int $country_id = 0;

    public int $filter = 0;

    public $page = [['id' => 10, 'name' => '10'], ['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100']];

    public int $perPage = 10; // Default jumlah data per halaman

    public bool $editModal = false; // Untuk menampilkan modal

    public ?Satuan $editingSatuan = null; // Menyimpan data Satuan yang sedang diedit

    public string $editingName = '';
    public string $editingDeskripsi = ''; // Menyimpan nilai input untuk nama Satuan

    public bool $createModal = false; // Untuk menampilkan modal create

    public string $newSatuanName = '';
    public string $newSatuanDeskripsi = ''; // Untuk menyimpan input nama Satuan baru

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
        $kategori = Satuan::findOrFail($id);
        $kategori->delete();
        $this->warning("Satuan $kategori->name akan dihapus", position: 'toast-top');
    }

    public function create(): void
    {
        $this->newSatuanName = ''; // Reset input sebelum membuka modal
        $this->newSatuanDeskripsi = '';
        $this->createModal = true;
    }

    public function saveCreate(): void
    {
        $this->validate([
            'newSatuanName' => 'required|string|max:255',
            'newSatuanDeskripsi' => 'nullable',
        ]);

        Satuan::create(['name' => $this->newSatuanName, 'deskripsi' => $this->newSatuanDeskripsi]);

        $this->createModal = false;
        $this->success('Satuan created successfully.', position: 'toast-top');
    }

    public function edit($id): void
    {
        $this->editingSatuan = Satuan::find($id);

        if ($this->editingSatuan) {
            $this->editingName = $this->editingSatuan->name;
            $this->editingDeskripsi = $this->editingSatuan->deskripsi;
            $this->editModal = true; // Tampilkan modal
        }
    }

    public function saveEdit(): void
    {
        if ($this->editingSatuan) {
            $this->validate([
                'editingName' => 'required|string|max:255',
                'editingDeskripsi' => 'nullable',
            ]);
            $this->editingSatuan->update(['name' => $this->editingName, 'deskripsi' => $this->editingDeskripsi, 'updated_at' => now()]);
            $this->editModal = false;
            $this->success('Satuan updated successfully.', position: 'toast-top');
        }
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-64'],
            ['key' => 'deskripsi', 'label' => 'Deskripsi', 'class' => 'w-100'],
            ['key' => 'barangs_count', 'label' => 'Barang', 'class' => 'w-64'], // Gunakan `barangs_count`
            ['key' => 'created_at', 'label' => 'Tanggal dibuat', 'class' => 'w-30'],
        ];
    }

    public function satuans(): LengthAwarePaginator
    {
        return Satuan::query()
            ->withCount('barangs') // Menghitung jumlah users di setiap Satuan
            ->when($this->search, fn(Builder $q) => $q->where('name', 'like', "%$this->search%"))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'satuans' => $this->satuans(),
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
    <x-header title="Satuans" separator progress-indicator>
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
        <x-table :headers="$headers" :rows="$satuans" :sort-by="$sortBy" with-pagination
            @row-click="$wire.edit($event.detail.id)">
            @scope('cell_satuan_count', $satuan)
                <span>{{ $satuan->satuan_count }}</span>
            @endscope
            @scope('actions', $satuans)
                <x-button icon="o-trash" wire:click="delete({{ $satuans['id'] }})"
                    wire:confirm="Yakin ingin menghapus {{ $satuans['name'] }}?" spinner
                    class="btn-ghost btn-sm text-red-500" />
            @endscope
        </x-table>
    </x-card>

    <x-modal wire:model="createModal" title="Create Satuan">
        <div class="grid gap-4">
            <x-input label="Satuan Name" wire:model.live="newSatuanName" />
            <x-textarea label="Satuan Deskripsi" wire:model.live="newSatuanDeskripsi" placeholder="Here ..." />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" icon="o-x-mark" @click="$wire.createModal=false" />
            <x-button label="Create" icon="o-check" class="btn-primary" wire:click="saveCreate" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="editModal" title="Edit Satuan">
        <div class="grid gap-4">
            <x-input label="Satuan Name" wire:model.live="editingName" />
            <x-textarea label="Satuan Deskripsi" wire:model.live="editingDeskripsi" placeholder="Here ..." />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" icon="o-x-mark" @click="$wire.editModal=false" />
            <x-button label="Save" icon="o-check" class="btn-primary" wire:click="saveEdit" />
        </x-slot:actions>
    </x-modal>
</div>
