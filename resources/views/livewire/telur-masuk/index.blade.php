<?php

use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Client;
use App\Models\Barang;
use App\Models\User;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exports\PembelianTelurExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

new class extends Component {
    use Toast;
    use WithPagination;

    public $today;
    public function mount(): void
    {
        $this->today = Carbon::today();
    }

    public string $search = '';
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];
    public int $filter = 0;
    public int $perPage = 10;
    public int $client_id = 0;

    public $tipePeternakOptions = [['id' => 'Elf', 'name' => 'Elf'], ['id' => 'Kuning', 'name' => 'Kuning'], ['id' => 'Merah', 'name' => 'Merah'], ['id' => 'Rumah', 'name' => 'Rumah']];
    public ?string $tipePeternak = null; // <- value yang dipilih

    public bool $exportModal = false; // ✅ Modal export
    // ✅ Tambah tanggal untuk filter export
    public ?string $startDate = null;
    public ?string $endDate = null;

    public $page = [['id' => 10, 'name' => '10'], ['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100']];

    public function clear(): void
    {
        $this->reset(['search', 'client_id', 'tipePeternak', 'filter', 'startDate', 'endDate']);
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

        return Excel::download(new PembelianTelurExport($this->startDate, $this->endDate), 'pembelian-telur.xlsx');
    }

    public function delete($id): void
    {
        $transaksi = Transaksi::find($id)->load('details');
        if (!$transaksi) {
            $this->error('Transaksi tidak ditemukan.');
            return;
        }

        // 1️⃣ Rollback stok & HPP untuk semua barang dalam transaksi ini
        foreach ($transaksi->details as $oldDetail) {
            $barang = Barang::find($oldDetail->barang_id);
            if (!$barang) {
                continue;
            }

            // Hitung stok baru berdasarkan semua transaksi lain (tanpa transaksi ini)
            $stokDebit = DetailTransaksi::where('barang_id', $barang->id)->where('transaksi_id', '!=', $transaksi->id)->whereHas('transaksi', fn($q) => $q->where('type', 'Debit'))->whereHas('kategori', fn($q) => $q->where('type', 'Aset'))->sum('kuantitas');

            $stokKredit = DetailTransaksi::where('barang_id', $barang->id)->where('transaksi_id', '!=', $transaksi->id)->whereHas('transaksi', fn($q) => $q->where('type', 'Kredit'))->whereHas('kategori', fn($q) => $q->where('type', 'Aset'))->sum('kuantitas');

            $stokBaru = $stokDebit - $stokKredit;

            // Hitung ulang HPP dari semua transaksi pembelian lain
            $totalHarga = DetailTransaksi::where('barang_id', $barang->id)->where('transaksi_id', '!=', $transaksi->id)->whereHas('transaksi', fn($q) => $q->where('type', 'Debit'))->whereHas('kategori', fn($q) => $q->where('type', 'Aset'))->sum(\DB::raw('value * kuantitas'));

            $totalQty = DetailTransaksi::where('barang_id', $barang->id)->where('transaksi_id', '!=', $transaksi->id)->whereHas('transaksi', fn($q) => $q->where('type', 'Debit'))->whereHas('kategori', fn($q) => $q->where('type', 'Aset'))->sum('kuantitas');

            $hppBaru = $totalQty > 0 ? $totalHarga / $totalQty : 0;

            // Jika stok <= 0, reset HPP ke 0
            if ($stokBaru <= 0) {
                $hppBaru = 0;
            }

            $barang->update([
                'stok' => $stokBaru,
                'hpp' => $hppBaru,
            ]);
        }

        $client = Client::find($transaksi->client_id);
        $client->decrement('titipan', (int) $transaksi->total);

        $suffix = substr($transaksi->invoice, -4);
        $hutang = Transaksi::where('invoice', 'like', "%-UTG-$suffix")->first();
        $hutang->details()->delete();
        $hutang->delete();

        // 2️⃣ Hapus detail transaksi
        $transaksi->details()->delete();

        // 3️⃣ Hapus transaksi utama
        $transaksi->delete();

        $this->warning("Transaksi ID $id dan semua detailnya berhasil dihapus.", position: 'toast-top');
    }

    public function headers(): array
    {
        return [['key' => 'invoice', 'label' => 'Invoice', 'class' => 'w-24'], ['key' => 'name', 'label' => 'Rincian', 'class' => 'w-48'], ['key' => 'tanggal', 'label' => 'Tanggal', 'class' => 'w-16'], ['key' => 'client.name', 'label' => 'Client', 'class' => 'w-16'], ['key' => 'client.keterangan', 'label' => 'Tipe Client', 'class' => 'w-16'], ['key' => 'total', 'label' => 'Total', 'class' => 'w-24', 'format' => ['currency', 0, 'Rp']]];
    }

    public function transaksi(): LengthAwarePaginator
    {
        return Transaksi::query()
            ->with(['client:id,name,keterangan', 'details.kategori:id,name,type'])
            ->where('invoice', 'like', '%-TLR-%')
            ->where('type', 'Debit')
            ->whereHas('details.kategori', fn(Builder $q) => $q->where('name', 'like', '%Stok Telur%'))
            ->when($this->tipePeternak, function (Builder $q) {
                $q->whereHas('client', function ($query) {
                    $query->where('keterangan', $this->tipePeternak);
                });
            })
            ->when($this->search, fn(Builder $q) => $q->where(fn($query) => $query->where('name', 'like', "%{$this->search}%")->orWhere('invoice', 'like', "%{$this->search}%")))
            ->when($this->client_id, fn(Builder $q) => $q->where('client_id', $this->client_id))
            ->when($this->startDate, fn(Builder $q) => $q->whereDate('tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn(Builder $q) => $q->whereDate('tanggal', '<=', $this->endDate))
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
            if ($this->tipePeternak != 0) {
                $this->filter++;
            }
            if ($this->startDate != null) {
                $this->filter++;
            }
        }

        return [
            'transaksi' => $this->transaksi(),
            'client' => Client::where('type', 'like', '%Peternak%')->get(),
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
    <x-header title="Transaksi Pembelian Telur" separator progress-indicator>
        <x-slot:actions>
            <div class="flex flex-row sm:flex-row gap-2">
                <x-button wire:click="openExportModal" icon="fas.download" primary>
                    Export Excel
                </x-button>
                <x-button label="Create" link="/telur-masuk/create" responsive icon="o-plus" class="btn-primary" />
            </div>
        </x-slot:actions>
    </x-header>

    <!-- FILTERS -->
    <div class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end mb-4">
        <div class="md:col-span-1">
            <x-select label="Show entries" :options="$pages" wire:model.live="perPage" class="w-full" />
        </div>

        <div class="md:col-span-6">
            <x-input placeholder="Cari Invoice..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass"
                class="w-full" />
        </div>

        <div class="md:col-span-1">
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel"
                badge="{{ $this->filter }}" badge-classes="badge-primary" class="w-full md:w-auto" />
        </div>
    </div>

    <!-- TABLE -->
    <x-card class="overflow-x-auto">
        <x-table :headers="$headers" :rows="$transaksi" :sort-by="$sortBy" with-pagination
            link="telur-masuk/{id}/show?invoice={invoice}">
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
                            link="/telur-masuk/{{ $transaksi->id }}/edit?invoice={{ $transaksi->invoice }}"
                            class="btn-ghost btn-sm text-yellow-500" />
                    @endif
                </div>
            @endscope
        </x-table>
    </x-card>

    <!-- DRAWER FILTER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button
        class="w-full sm:w-[90%] md:w-1/2 lg:w-1/3">
        <div class="grid gap-5">
            <x-input placeholder="Cari Invoice..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />
            <x-choices-offline placeholder="Pilih Client" wire:model.live="client_id" :options="$client" searchable
                single />
            <x-select placeholder="Pilih Peternak" wire:model.live="tipePeternak" :options="$tipePeternakOptions" icon="o-tag"
                placeholder-value="0" />

            <!-- ✅ Tambahkan Filter Tanggal -->
            <x-input label="Tanggal Awal" type="date" wire:model.live="startDate" />
            <x-input label="Tanggal Akhir" type="date" wire:model.live="endDate" />

        </div>

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner class="w-full sm:w-auto" />
            <x-button label="Done" icon="o-check" class="btn-primary w-full sm:w-auto" @click="$wire.drawer=false" />
        </x-slot:actions>
    </x-drawer>

    <!-- MODAL EXPORT -->
    <x-modal wire:model="exportModal" title="Export Data" separator>
        <div class="grid gap-4">
            <x-input label="Start Date" type="date" wire:model="startDate" />
            <x-input label="End Date" type="date" wire:model="endDate" />
        </div>

        <x-slot:actions>
            <x-button label="Batal" @click="$wire.exportModal=false" />
            <x-button label="Export" class="btn-primary w-full sm:w-auto" wire:click="export" spinner />
        </x-slot:actions>
    </x-modal>
</div>
