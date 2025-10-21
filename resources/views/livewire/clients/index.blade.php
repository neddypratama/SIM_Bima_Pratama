<?php

use App\Models\Client;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exports\ClientExport;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';

    public bool $drawer = false;

    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    public $tipeClientOptions = [['id' => 'Karyawan', 'name' => 'Karyawan'], ['id' => 'Peternak', 'name' => 'Peternak'], ['id' => 'Pedagang', 'name' => 'Pedagang'], ['id' => 'Supplier', 'name' => 'Supplier']];

    public ?string $tipeClient = null; // <- value yang dipilih

    public int $filter = 0;

    public $page = [['id' => 10, 'name' => '10'], ['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100']];

    public int $perPage = 10; // Default jumlah data per halaman

    public bool $editModal = false; // Untuk menampilkan modal

    public ?Client $editingClient = null; // Menyimpan data Client yang sedang diedit

    public string $editingName = '';
    public string $editingAlamat = ''; // Menyimpan nilai input untuk nama Client
    public ?string $editingType = null;
    public int $editingBon = 0;
    public int $editingTitipan = 0;

    public bool $createModal = false; // Untuk menampilkan modal create

    public string $newClientName = '';
    public string $newClientAlamat = ''; // Untuk menyimpan input nama Client baru
    public ?string $newClientType = null;
    public int $newClientBon = 0;
    public int $newClientTitipan = 0;

    public function create(): void
    {
        $this->newClientName = ''; // Reset input sebelum membuka modal
        $this->newClientAlamat = '';
        $this->newClientType = null;
        $this->newClientBon = 0;
        $this->newClientTitipan = 0;
        if (Auth::user()->role_id == 1) {
            # code...
            $this->createModal = true;
        }
    }

    public function saveCreate(): void
    {
        $this->validate([
            'newClientName' => 'required|string|max:255|unique:clients,name',
            'newClientAlamat' => 'nullable',
            'newClientType' => 'required|in:Karyawan,Peternak,Pedagang,Supllier',
            'newClientBon' => 'nullable|integer',
            'newClientTitipan' => 'nullable|integer',
        ]);

        Client::create(['name' => $this->newClientName, 'alamat' => $this->newClientAlamat, 'type' => $this->newClientType, 'bon' => $this->newClientBon, 'titipan' => $this->newClientTitipan]);

        $this->createModal = false;
        $this->success('Client created successfully.', position: 'toast-top');
    }

    public function edit($id): void
    {
        $this->editingClient = Client::find($id);

        if ($this->editingClient) {
            $this->editingName = $this->editingClient->name;
            $this->editingAlamat = $this->editingClient->alamat;
            $this->editingType = $this->editingClient->type;
            $this->editingBon = $this->editingClient->bon;
            $this->editingTitipan = $this->editingClient->titipan;
            if (Auth::user()->role_id == 1) {
                # code...
                $this->editModal = true; // Tampilkan modal
            }
        }
    }

    public function saveEdit(): void
    {
        if ($this->editingClient) {
            $this->validate([
                'editingName' => 'required|string|max:255|unique:clients,name',
                'editingAlamat' => 'nullable',
                'editingType' => 'required|in:Karyawan,Peternak,Pedagang,Supllier',
                'editingBon' => 'nullable|integer',
                'editingTitipan' => 'nullable|integer',
            ]);
            $this->editingClient->update(['name' => $this->editingName, 'alamat' => $this->editingAlamat, 'type' => $this->editingType, 'bon' => $this->editingBon, 'titipan' => $this->editingTitipan, 'updated_at' => now()]);
            $this->editModal = false;
            $this->success('Client updated successfully.', position: 'toast-top');
        }
    }

    // Clear filters
    public function clear(): void
    {
        $this->reset();
        $this->resetPage();
        $this->success('Filters cleared.', position: 'toast-top');
    }

    public function export(): mixed
    {
        $this->success('Export dimulai...', position: 'toast-top');

        return Excel::download(new ClientExport(), 'client.xlsx');
    }

    // Delete action
    public function delete($id): void
    {
        $client = Client::findOrFail($id);
        $client->delete();
        $this->warning("Client $client->name akan dihapus", position: 'toast-top');
    }

    // Table headers
    public function headers(): array
    {
        return [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'type', 'label' => 'Tipe Client'], ['key' => 'name', 'label' => 'Name', 'class' => 'w-64'], ['key' => 'alamat', 'label' => 'Alamat', 'sortable' => false], ['key' => 'bon', 'label' => 'Bon'], ['key' => 'titipan', 'label' => 'Titipan', 'class' => 'w-1']];
    }

    public function clients(): LengthAwarePaginator
    {
        return Client::query()
            ->with('transaksi.details.kategori') // agar eager load, lebih hemat query
            ->when($this->search, fn(Builder $q) => $q->where('name', 'like', "%$this->search%"))
            ->when($this->tipeClient, fn(Builder $q) => $q->where('type', $this->tipeClient))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        if ($this->filter >= 0 && $this->filter < 2) {
            if (!$this->search == null) {
                $this->filter = 1;
            } else {
                $this->filter = 0;
            }
            if (!$this->tipeClient == null) {
                $this->filter += 1;
            }
        }
        return [
            'clients' => $this->clients(),
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
    <x-header title="Daftar Klien" separator progress-indicator>
        <x-slot:actions>
            <div class="flex flex-row sm:flex-row gap-2">
                <x-button wire:click="export" icon="fas.download" primary>Export Excel</x-button>
                <x-button label="Create" @click="$wire.create()" responsive icon="o-plus" class="btn-primary" />
            </div>
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

    <!-- TABLE wire:poll.5s="Clients"  -->
    <x-card>
        <x-table :headers="$headers" :rows="$clients" :sort-by="$sortBy" with-pagination
            @row-click="$wire.edit($event.detail.id)">

            {{-- Kolom Bon --}}
            @scope('cell_bon', $client)
                <span class="font-bold text-blue-600">
                    {{ number_format($client->bon, 0, ',', '.') }}
                </span>
            @endscope

            {{-- Kolom Titipan --}}
            @scope('cell_titipan', $client)
                <span class="font-bold text-green-600">
                    {{ number_format($client->titipan, 0, ',', '.') }}
                </span>
            @endscope

            {{-- Tombol Aksi --}}
            @scope('actions', $client)
                <x-button icon="o-trash" wire:click="delete({{ $client['id'] }})"
                    wire:confirm="Yakin ingin menghapus {{ $client['name'] }}?" spinner
                    class="btn-ghost btn-sm text-red-500" />
            @endscope
        </x-table>

    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-input placeholder="Name..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
            <x-select placeholder="Tipe Client" wire:model.live="tipeClient" :options="$tipeClientOptions" icon="o-flag" />
        </div>

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer=false" />
        </x-slot:actions>
    </x-drawer>

    <x-modal wire:model="createModal" title="Create Client">
        <div class="grid gap-4">
            <x-input label="Client Name" wire:model.live="newClientName" />
            <x-textarea label="Client Alamat" wire:model.live="newClientAlamat" placeholder="Here ..." />
            <x-select label="Tipe Client" placeholder="Select Tipe Client" wire:model.live="newClientType"
                :options="$tipeClientOptions" icon="o-flag" />
            <x-input label="Client Bon" wire:model.live="newClientBon" />
            <x-input label="Client Titipan" wire:model.live="newClientTitipan" />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" icon="o-x-mark" @click="$wire.createModal=false" />
            <x-button label="Create" icon="o-check" class="btn-primary" wire:click="saveCreate" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="editModal" title="Edit Client">
        <div class="grid gap-4">
            <x-input label="Client Name" wire:model.live="editingName" />
            <x-textarea label="Client Alamat" wire:model.live="editingAlamat" placeholder="Here ..." />
            <x-select label="Tipe Client" placeholder="Select Tipe Client" wire:model.live="editingType"
                :options="$tipeClientOptions" icon="o-flag" />
            <x-input label="Client Bon" wire:model.live="editingBon" />
            <x-input label="Client Titipan" wire:model.live="editingTitipan" />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" icon="o-x-mark" @click="$wire.editModal=false" />
            <x-button label="Save" icon="o-check" class="btn-primary" wire:click="saveEdit" />
        </x-slot:actions>
    </x-modal>
</div>
