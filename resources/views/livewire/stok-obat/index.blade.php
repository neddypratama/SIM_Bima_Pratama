<?php

use App\Models\Stok;
use App\Models\Barang;
use App\Models\Transaksi;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exports\StokObatExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

new class extends Component {
    use Toast;
    use WithPagination;

    public $today;
    public function mount(): void
    {
        $this->today = \Carbon\Carbon::today();
    }

    public string $search = '';
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];
    public int $filter = 0;
    public int $perPage = 10;
    public int $barang_id = 0;

    public bool $exportModal = false; // ✅ Modal export
    // ✅ Tambah tanggal untuk filter export
    public ?string $startDate = null;
    public ?string $endDate = null;

    public $page = [['id' => 10, 'name' => '10'], ['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100']];

    public function clear(): void
    {
        $this->reset(['search', 'barang_id', 'filter']);
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

        return Excel::download(new StokObatExport($this->startDate, $this->endDate), 'stok-obat.xlsx');
    }

    public function delete($id): void
    {
        $stok = Stok::with('barang')->findOrFail($id);
        $inv = substr($stok->invoice, -4);

        $kotor = Transaksi::where('invoice', 'like', "%$inv")
            ->whereHas('details.kategori', fn($q) => $q->where('name', 'Stok Return'))
            ->first();
        $kotor->details()->delete();
        $kotor->delete();

        $pecah = Transaksi::where('invoice', 'like', "%$inv")
            ->whereHas('details.kategori', fn($q) => $q->where('name', 'like', '%Barang Kadaluarsa'))
            ->first();
        $pecah->details()->delete();
        $pecah->delete();

        $telur = Transaksi::where('invoice', 'like', "%$inv")
            ->whereHas('details.kategori', fn($q) => $q->where('name', 'Stok Obat-Obatan'))
            ->get();
        foreach ($telur as $key) {
            $key->details()->delete();
            $key->delete();
        }

        $barang = $stok->barang;
        if ($barang) {
            // kembalikan stok ke kondisi sebelum transaksi
            $stok_awal = $barang->stok - $stok->tambah + ($stok->kurang + $stok->kotor + $stok->rusak);
            $barang->update(['stok' => max(0, $stok_awal)]);
        }
        $stok->delete();

        $this->warning("Stok $id berhasil dihapus", position: 'toast-top');
    }

    public function headers(): array
    {
        return [['key' => 'invoice', 'label' => 'Invoice', 'class' => 'w-36'], ['key' => 'barang.name', 'label' => 'Barang', 'class' => 'w-36'], ['key' => 'tanggal', 'label' => 'Tanggal', 'class' => 'w-16'], ['key' => 'user.name', 'label' => 'Pembuat', 'class' => 'w-16'], ['key' => 'tambah', 'label' => ' Tambah', 'class' => 'w-16'], ['key' => 'kurang', 'label' => ' Kurang', 'class' => 'w-16'], ['key' => 'kotor', 'label' => ' Return', 'class' => 'w-16'], ['key' => 'rusak', 'label' => ' Kadaluarsa', 'class' => 'w-16']];
    }

    public function transaksi(): LengthAwarePaginator
    {
        return Stok::query()
            ->with(['barang:id,name', 'user:id,name'])
            ->whereHas('barang.jenis', function ($q) {
                $q->where('name', 'like', 'Obat-Obatan');
            })
            ->when($this->search, function (Builder $query) {
                $query
                    ->whereHas('barang', function ($q) {
                        $q->where('name', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('user', function ($q) {
                        $q->where('name', 'like', "%{$this->search}%");
                    })
                    ->orWhere('invoice', 'like', "%{$this->search}%");
            })
            ->when($this->barang_id, fn(Builder $q) => $q->where('barang_id', $this->barang_id))
            ->when(
                !empty($this->sortBy),
                function (Builder $q) {
                    $sortBy = $this->sortBy;
                    $column = $sortBy['column'] ?? 'created_at';
                    $direction = $sortBy['direction'] ?? 'desc';
                    $q->orderBy($column, $direction);
                },
                fn(Builder $q) => $q->orderBy('created_at', 'desc'),
            )
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        if ($this->filter >= 0 && $this->filter < 3) {
            $this->filter = 0;
            if (!empty($this->search)) {
                $this->filter++;
            }
            if ($this->barang_id != 0) {
                $this->filter++;
            }
        }
        return [
            'transaksi' => $this->transaksi(),
            'barang' => Barang::whereHas('jenis', function ($q) {
                $q->where('name', 'like', 'Obat-Obatan');
            })->get(),
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

<div class="p-4 space-y-6">
    <x-header title="Transaksi Stok Obat" separator progress-indicator>
        <x-slot:actions>
            <div class="flex flex-row sm:flex-row gap-2">
                <x-button wire:click="openExportModal" icon="fas.download" primary>Export Excel</x-button>
                <x-button label="Create" link="/stok-obat/create" responsive icon="o-plus" class="btn-primary" />
            </div>
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end mb-4">
        <div class="md:col-span-1">
            <x-select label="Show entries" :options="$pages" wire:model.live="perPage" />
        </div>
        <div class="md:col-span-6">
            <x-input placeholder="Cari..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </div>
        <div class="md:col-span-1">
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel"
                badge="{{ $this->filter }}" badge-classes="badge-primary" />
        </div>
    </div>

    <!-- TABLE -->
    <x-card class="overflow-x-auto">
        <x-table :headers="$headers" :rows="$transaksi" :sort-by="$sortBy" with-pagination
            link="stok-obat/{id}/show?barang={barang.name}">
            @scope('cell-kategori.name', $transaksi)
                {{ $transaksi->kategori?->name ?? '-' }}
            @endscope

            @scope('actions', $transaksi)
                <div class="flex">
                    @if (Auth::user()->role_id == 1)
                        <x-button icon="o-trash" wire:click="delete({{ $transaksi->id }})"
                            wire:confirm="Yakin ingin menghapus transaksi {{ $transaksi->invoice }} ini?" spinner
                            class="btn-ghost btn-sm text-red-500" />
                    @endif
                    @if (Auth::user()->role_id == 1 ||
                            (Carbon::parse($transaksi->tanggal)->isSameDay($this->today) && $transaksi->user_id == Auth::user()->id))
                        <x-button icon="o-pencil"
                            link="/stok-obat/{{ $transaksi->id }}/edit?invoice={{ $transaksi->invoice }}"
                            class="btn-ghost btn-sm text-yellow-500" />
                    @endif
                </div>
            @endscope
        </x-table>
    </x-card>

    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button
        class="w-full sm:w-[90%] md:w-1/2 lg:w-1/3">
        <div class="grid gap-5">
            <x-input placeholder="Cari Invoice..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />

            <x-choices-offline placeholder="Pilih Barang" wire:model.live="barang_id" :options="$barang" icon="o-flag"
                single searchable />
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
