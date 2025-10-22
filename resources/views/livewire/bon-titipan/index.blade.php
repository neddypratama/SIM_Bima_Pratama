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

    public ?string $tipeClient = null;

    public $tipePeternakOptions = [['id' => 'Elf', 'name' => 'Elf'], ['id' => 'Kuning', 'name' => 'Kuning'], ['id' => 'Merah', 'name' => 'Merah'], ['id' => 'Rumah', 'name' => 'Rumah']];
    public ?string $tipePeternak = null; // <- value yang dipilih

    public int $filter = 0;

    public $page = [['id' => 10, 'name' => '10'], ['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100']];

    public int $perPage = 10;

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

    public function delete($id): void
    {
        $client = Client::findOrFail($id);
        $client->delete();
        $this->warning("Client $client->name akan dihapus", position: 'toast-top');
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'type', 'label' => 'Tipe Client', 'class' => 'w-48'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-48'],
            ['key' => 'alamat', 'label' => 'Alamat', 'sortable' => false, 'class' => 'w-64'],
            ['key' => 'keterangan', 'label' => 'Keterangan', 'sortable' => false, 'class' => 'w-24'],
            ['key' => 'bon', 'label' => 'Bon', 'class' => 'w-36'],
            ['key' => 'titipan', 'label' => 'Titipan', 'class' => 'w-36'],
            ['key' => 'sisa', 'label' => 'Sisa', 'class' => 'w-36'], // ✅ Tambahan kolom baru
        ];
    }

    public function clients(): LengthAwarePaginator
    {
        return Client::query()->with('transaksi.details.kategori')->when($this->search, fn(Builder $q) => $q->where('name', 'like', "%$this->search%"))->when($this->tipeClient, fn(Builder $q) => $q->where('type', $this->tipeClient))->when($this->tipePeternak, fn(Builder $q) => $q->where('keterangan', $this->tipePeternak))->orderBy(...array_values($this->sortBy))->paginate($this->perPage);
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
             if ($this->tipePeternak != 0) {
                $this->filter++;
            }
        }
        return [
            'clients' => $this->clients(),
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
    <x-header title="Daftar Klien" separator progress-indicator />

    <!-- FILTERS -->
    <div class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end mb-4">
        <div class="md:col-span-1">
            <x-select label="Show entries" :options="$pages" wire:model.live="perPage" />
        </div>
        <div class="md:col-span-6">
            <x-input placeholder="Name..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </div>
        <div class="md:col-span-1">
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel"
                badge="{{ $this->filter }}" badge-classes="badge-primary" />
        </div>
    </div>

    <!-- TABLE -->
    <x-card>
        <x-table :headers="$headers" :rows="$clients" :sort-by="$sortBy" with-pagination>

            {{-- Kolom Bon --}}
            @scope('cell_bon', $client)
                <span class="font-bold text-blue-600">
                    Rp {{ number_format($client->bon, 0, ',', '.') }}
                </span>
            @endscope

            {{-- Kolom Titipan --}}
            @scope('cell_titipan', $client)
                <span class="font-bold text-green-600">
                    Rp {{ number_format($client->titipan, 0, ',', '.') }}
                </span>
            @endscope

            {{-- ✅ Kolom Sisa (Bon - Titipan) --}}
            @scope('cell_sisa', $client)
                @php
                    $sisa = $client->bon - $client->titipan;
                    $warna = $sisa > 0 ? 'text-red-600' : ($sisa < 0 ? 'text-blue-600' : 'text-gray-600');
                @endphp
                <span class="font-bold {{ $warna }}">
                    Rp {{ number_format($sisa, 0, ',', '.') }}
                </span>
            @endscope

        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-input placeholder="Name..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
            <x-select placeholder="Tipe Client" wire:model.live="tipeClient" :options="$tipeClientOptions" icon="o-flag" />
            <x-select placeholder="Pilih Peternak" wire:model.live="tipePeternak" :options="$tipePeternakOptions" icon="o-tag"
                placeholder-value="0" />
        </div>

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer=false" />
        </x-slot:actions>
    </x-drawer>
</div>
