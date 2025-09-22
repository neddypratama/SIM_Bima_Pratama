<?php

use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Barang;
use App\Models\Client;
use App\Models\User;
use App\Models\Kategori;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exports\PiutangExport;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];
    public int $filter = 0;
    public int $perPage = 10;
    public int $client_id = 0;
    public int $kategori_id = 0;

    public bool $exportModal = false; // ✅ Modal export
    // ✅ Tambah tanggal untuk filter export
    public ?string $startDate = null;
    public ?string $endDate = null;

    public $page = [['id' => 10, 'name' => '10'], ['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100']];

    public function clear(): void
    {
        $this->reset(['search', 'client_id', 'kategori_id', 'filter']);
        $this->resetPage();
        $this->success('Filters cleared.', position: 'toast-top');
    }

    public function openExportModal(): void
    {
        $this->exportModal = true;
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
    }

    public function export(): mixed
    {
        if (!$this->startDate || !$this->endDate) {
            $this->error('Pilih tanggal terlebih dahulu.');
            return null; // ✅ Sekarang tetap return sesuatu
        }

        $this->exportModal = false;
        $this->success('Export dimulai...', position: 'toast-top');

        return Excel::download(new PiutangExport($this->startDate, $this->endDate), 'piutang.xlsx');
    }

    public function delete($id): void
    {
        $transaksi = Transaksi::findOrFail($id);

        // ✅ Unlink semua transaksi yang punya linked_id = $id
        Transaksi::where('linked_id', $id)->update(['linked_id' => null]);

        // ✅ Hapus semua detail dulu
        $transaksi->details()->delete();

        // ✅ Baru hapus transaksi utamanya
        $transaksi->delete();

        $this->warning("Transaksi $id dan semua detailnya berhasil dihapus", position: 'toast-top');
    }

    public function headers(): array
    {
        return [['key' => 'invoice', 'label' => 'Invoice', 'class' => 'w-24'], ['key' => 'name', 'label' => 'Rincian', 'class' => 'w-48'], ['key' => 'tanggal', 'label' => 'Tanggal', 'class' => 'w-16'], ['key' => 'client.name', 'label' => 'Client', 'class' => 'w-16'], ['key' => 'kategori.name', 'label' => 'Kategori', 'class' => 'w-24'], ['key' => 'total', 'label' => 'Total', 'class' => 'w-32', 'format' => ['currency', 0, 'Rp']]];
    }

    public function transaksi(): LengthAwarePaginator
    {
        return Transaksi::query()
            ->with(['client:id,name', 'kategori:id,name,type'])
            ->whereHas('kategori', function (Builder $q) {
                $q->where('type', 'like', '%Aset%')->Where('name', 'like', '%Piutang%');
            })
            ->when($this->search, function (Builder $q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")->orWhere('invoice', 'like', "%{$this->search}%");
                });
            })
            ->when($this->client_id, fn(Builder $q) => $q->where('client_id', $this->client_id))
            ->when($this->kategori_id, fn(Builder $q) => $q->where('kategori_id', $this->kategori_id))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        if ($this->filter >= 0 && $this->filter < 3) {
            $this->filter = 0;
            if (!empty($this->search)) {
                $this->filter++;
            }
            if ($this->client_id != 0) {
                $this->filter++;
            }
            if ($this->kategori_id != 0) {
                $this->filter++;
            }
        }

        return [
            'transaksi' => $this->transaksi(),
            'client' => Client::all()
                ->groupBy('type')
                ->mapWithKeys(
                    fn($group, $type) => [
                        $type => $group->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()->toArray(),
                    ],
                )
                ->toArray(),
            'kategori' => Kategori::where('name', 'like', 'Piutang%')->get(),
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
    <x-header title="Transaksi Piutang" separator progress-indicator>
        <x-slot:actions>
            <x-button wire:click="openExportModal" icon="fas.download" primary>Export Excel</x-button>
            <x-button label="Create" link="/piutang/create" responsive icon="o-plus" class="btn-primary" />
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
            link="piutang/{id}/edit?invoice={invoice}">
            @scope('cell-kategori.name', $transaksi)
                {{ $transaksi->kategori?->name ?? '-' }}
            @endscope

            @scope('actions', $transaksi)
                <div class="flex">
                    <x-button icon="o-trash" wire:click="delete({{ $transaksi->id }})"
                        wire:confirm="Yakin ingin menghapus transaksi {{ $transaksi->invoice }} ini?" spinner
                        class="btn-ghost btn-sm text-red-500" />
                    <x-button icon="o-eye" link="/piutang/{{ $transaksi->id }}/show?invoice={{ $transaksi->invoice }}"
                        class="btn-ghost btn-sm text-yellow-500" />
                </div>
            @endscope
        </x-table>
    </x-card>

    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <div class="grid gap-5">
            <x-input placeholder="Cari Invoice..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />

            <x-select-group placeholder="Pilih Client" wire:model.live="client_id" :options="$client" option-label="name"
                option-value="id" icon="o-user" placeholder-value="0" />

            <x-select placeholder="Pilih Kategori" wire:model.live="kategori_id" :options="$kategori" option-label="name"
                option-value="id" icon="o-user" placeholder-value="0" />
        </div>

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer=false" />
        </x-slot:actions>
    </x-drawer>

    <!-- ✅ MODAL EXPORT -->
    <x-modal wire:model="exportModal" title="Export Data" separator>
        <div class="grid gap-4">
            <x-input label="Start Date" type="date" wire:model="startDate" />
            <x-input label="End Date" type="date" wire:model="endDate" />
        </div>
        <x-slot:actions>
            <x-button label="Batal" @click="$wire.exportModal=false" />
            <x-button label="Export" class="btn-primary" wire:click="export" spinner />
        </x-slot:actions>
    </x-modal>
</div>
