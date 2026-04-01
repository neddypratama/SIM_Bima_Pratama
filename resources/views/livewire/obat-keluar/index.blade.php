<?php

use App\Models\Transaksi;
use App\Models\StokBatch;
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
use App\Exports\PenjualanObatExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];
    public int $filter = 0;

    public int $client_id = 0;
    public int $kategori_id = 0;
    public int $barang_id = 0;

    public $tipePeternakOptions = [['id' => 'Elf', 'name' => 'Elf'], ['id' => 'Kuning', 'name' => 'Kuning'], ['id' => 'Merah', 'name' => 'Merah'], ['id' => 'Rumah', 'name' => 'Rumah']];
    public ?string $tipePeternak = null; // <- value yang dipilih

    public bool $exportModal = false; // ✅ Modal export
    // ✅ Tambah tanggal untuk filter export
    public ?string $startDate = null;
    public ?string $endDate = null;

    public ?string $selectedId = null;
    public ?string $selectedInv = null;
    public bool $statusModal = false;
    public ?string $status = null;

    public $page = [['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100'], ['id' => 500, 'name' => '500']];

    public int $perPage = 25; // Default jumlah data per halaman

    public $today;
    public function mount(): void
    {
        $this->today = \Carbon\Carbon::today();
    }

    public function clear(): void
    {
        $this->reset(['search', 'client_id', 'kategori_id', 'filter', 'startDate', 'endDate']);
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

        return Excel::download(new PenjualanObatExport($this->startDate, $this->endDate), 'penjualan-obat.xlsx');
    }

    public function delete($id): void
    {
        $transaksi = Transaksi::findOrFail($id);
        if ($transaksi->status == 'Selesai') {
            $this->error('Transaksi sudah selesai, tidak bisa dihapus.');
            return;
        }
        $transaksi->details()->delete();
        $transaksi->delete();

        $this->warning("Transaksi {$transaksi->invoice}, relasi transaksi, dan semua detailnya berhasil dihapus & stok dikembalikan", position: 'toast-top');
    }

    public function openStatusModal($id): void
    {
        $this->selectedId = $id;
        $this->selectedInv = Transaksi::find($id)->invoice ?? null;
        $this->status = Transaksi::find($id)->status ?? 'Perbaikan';
        $this->statusModal = true;
    }

    public function updateStatus(): void
    {
        DB::transaction(function () {
            $transaksi = Transaksi::findOrFail($this->selectedId);
            $detailTransaksi = $transaksi->details()->get();
            if ($this->status == 'Selesai') {
                $transaksi->update(['status' => 'Selesai']);

                $str = substr($transaksi->invoice, -4);
                $part = explode('-', $transaksi->invoice);
                $tanggal = $part[1];

                $invoice1 = 'INV-' . $tanggal . '-BON-' . $str;
                $invoice2 = 'INV-' . $tanggal . '-OBT-' . $str;
                $invoice3 = 'INV-' . $tanggal . '-HPP-' . $str;

                $kategoriObat = Kategori::where('name', 'Stok Obat-Obatan')->first();
                $kategoriHpp = Kategori::where('name', 'HPP')->first();
                $kategoriBon = Kategori::where('name', 'like', 'Piutang Peternak')->first();

                $bon = Transaksi::create([
                    'invoice' => $invoice1,
                    'name' => $transaksi->name,
                    'user_id' => $transaksi->user_id,
                    'tanggal' => $transaksi->tanggal,
                    'client_id' => $transaksi->client_id,
                    'type' => 'Debit',
                    'total' => $transaksi->total,
                    'status' => 'Selesai',
                ]);

                $totalHPP = 0;
                $hppPerBarang = [];

                foreach ($detailTransaksi as $item) {
                    $barang = Barang::find($item->barang_id);
                    $qtyJual = $item->kuantitas;

                    DetailTransaksi::create([
                        'transaksi_id' => $bon->id,
                        'kategori_id' => $kategoriBon->id,
                        'value' => $item->value, // harga satuan
                        'barang_id' => $item->barang_id ?? null,
                        'kuantitas' => $item->kuantitas ?? null,
                        'sub_total' => $item->value * ($item->kuantitas ?? 1), // harga total
                    ]);

                    /** =========================
                     * FIFO HPP
                     ========================== */
                    $hppBarang = 0;
                    $sisa = $qtyJual;

                    $batches = StokBatch::where('barang_id', $barang->id)->where('qty_sisa', '>', 0)->orderBy('tanggal')->orderBy('id')->lockForUpdate()->get();

                    foreach ($batches as $batch) {
                        if ($sisa <= 0) {
                            break;
                        }

                        $pakai = min($batch->qty_sisa, $sisa);

                        $hppBatch = $pakai * $batch->harga;

                        $hppBarang += $hppBatch;
                        $totalHPP += $hppBatch;

                        $batch->decrement('qty_sisa', $pakai);

                        // 🔥 SIMPAN KE PENJUALAN ASLI (INI KUNCI)
                        \App\Models\StokKeluarBatch::create([
                            'detail_transaksi_id' => $item->id, // ✅ PENJUALAN
                            'stok_batch_id' => $batch->id,
                            'qty' => $pakai,
                            'returned_qty' => 0,
                            'harga' => $batch->harga,
                        ]);

                        $sisa -= $pakai;
                    }

                    if ($sisa > 0) {
                        throw new \Exception("Stok FIFO {$barang->name} tidak mencukupi");
                    }

                    $hppPerBarang[$barang->id] = [
                        'total' => $hppBarang,
                        'qty' => $qtyJual,
                    ];
                }

                $hpp = Transaksi::create([
                    'invoice' => $invoice3,
                    'name' => $transaksi->name,
                    'user_id' => $transaksi->user_id,
                    'tanggal' => $transaksi->tanggal,
                    'client_id' => $transaksi->client_id,
                    'type' => 'Debit',
                    'total' => $totalHPP,
                    'status' => 'Selesai',
                ]);

                foreach ($hppPerBarang as $barangId => $data) {
                    $barang = Barang::find($barangId);

                    DetailTransaksi::create([
                        'transaksi_id' => $hpp->id,
                        'barang_id' => $barang->id,
                        'kategori_id' => $kategoriHpp->id,
                        'value' => $data['total'] / $data['qty'], // HPP per unit FIFO
                        'kuantitas' => $data['qty'],
                        'sub_total' => $data['total'], // TOTAL HPP BARANG
                    ]);
                }

                $stok = Transaksi::create([
                    'invoice' => $invoice2,
                    'name' => $transaksi->name,
                    'user_id' => $transaksi->user_id,
                    'tanggal' => $transaksi->tanggal,
                    'client_id' => $transaksi->client_id,
                    'type' => 'Kredit',
                    'total' => $totalHPP,
                    'status' => 'Selesai',
                ]);

                foreach ($hppPerBarang as $barangId => $data) {
                    $barang = Barang::find($barangId);

                    DetailTransaksi::create([
                        'transaksi_id' => $stok->id,
                        'barang_id' => $barang->id,
                        'kategori_id' => $kategoriObat->id,
                        'value' => $data['total'] / $data['qty'], // HPP per unit FIFO
                        'kuantitas' => $data['qty'],
                        'sub_total' => $data['total'], // TOTAL HPP BARANG
                    ]);
                }

                $this->success("Status transaksi {$transaksi->invoice} berhasil diubah menjadi Selesai", position: 'toast-top');
            } else {
                $transaksi->update(['status' => 'Batal']);
                $this->success("Status transaksi {$transaksi->invoice} berhasil diubah menjadi Batal", position: 'toast-top');
            }
        });

        $this->statusModal = false;
    }

    public function headers(): array
    {
        return [['key' => 'invoice', 'label' => 'Invoice', 'class' => 'w-24'], ['key' => 'name', 'label' => 'Rincian', 'class' => 'w-48'], ['key' => 'tanggal', 'label' => 'Tanggal', 'class' => 'w-16'], ['key' => 'client.name', 'label' => 'Client', 'class' => 'w-16'], ['key' => 'client.keterangan', 'label' => 'Tipe Client', 'class' => 'w-16'], ['key' => 'total', 'label' => 'Total', 'class' => 'w-24', 'format' => ['currency', 0, 'Rp']], ['key' => 'status', 'label' => 'Status', 'class' => 'w-16']];
    }

    public function transaksi(): LengthAwarePaginator
    {
        return Transaksi::query()
            ->with(['client:id,name,keterangan', 'details.kategori:id,name'])
            ->where('type', 'Kredit')
            ->whereHas('details.kategori', function (Builder $q) {
                $q->where('name', 'like', '%Penjualan Obat%');
            })
            ->when($this->search, function (Builder $q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")->orWhere('invoice', 'like', "%{$this->search}%");
                });
            })
            ->when($this->tipePeternak, function (Builder $q) {
                $q->whereHas('client', function ($query) {
                    $query->where('keterangan', $this->tipePeternak);
                });
            })
            // 📦 FILTER BARANG (BENAR)
            ->when($this->barang_id, function (Builder $q) {
                $q->whereHas('details', function ($q2) {
                    $q2->where('barang_id', $this->barang_id);
                });
            })
            ->when($this->client_id, fn(Builder $q) => $q->where('client_id', $this->client_id))
            ->when($this->startDate, fn(Builder $q) => $q->whereDate('tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn(Builder $q) => $q->whereDate('tanggal', '<=', $this->endDate))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        if ($this->filter >= 0 && $this->filter < 4) {
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
            if ($this->barang_id != 0) {
                $this->filter++;
            }
            if ($this->startDate != null) {
                $this->filter++;
            }
        }

        return [
            'transaksi' => $this->transaksi(),
            'barang' => Barang::with('jenis')
                ->whereHas('jenis', function ($q) {
                    $q->where('name', 'like', '%Obat%');
                })
                ->get(),
            'client' => Client::where('type', 'like', '%Pedagang%')->orWhere('type', 'like', '%Peternak%')->get(),
            'kategori' => Kategori::where('name', 'like', 'Penjualan Obat%')->get(),
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
    <x-header title="Transaksi Penjualan Obat" separator progress-indicator>
        <x-slot:actions>
            <div class="flex flex-row sm:flex-row gap-2">
                <x-button wire:click="openExportModal" icon="fas.download" primary>Export Excel</x-button>
                <x-button label="Create" link="/obat-keluar/create" responsive icon="o-plus" class="btn-primary" />
            </div>
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

    <x-card class="overflow-x-auto">
        <x-table :headers="$headers" :rows="$transaksi" :sort-by="$sortBy" with-pagination
            link="obat-keluar/{id}/show?invoice={invoice}">
            @scope('cell-kategori.name', $transaksi)
                {{ $transaksi->kategori?->name ?? '-' }}
            @endscope
            @scope('cell_status', $transaksi)
                @if ($transaksi->status == 'Selesai')
                    <span class="badge badge-success">{{ $transaksi->status }}</span>
                @elseif ($transaksi->status == 'Perbaikan')
                    <span class="badge badge-warning">{{ $transaksi->status }}</span>
                @else
                    <span class="badge badge-error">{{ $transaksi->status }}</span>
                @endif
            @endscope
            @scope('actions', $transaksi)
                <div class="flex">
                    @if (Auth::user()->role_id == 1)
                        <x-button icon="o-trash" wire:click="delete({{ $transaksi->id }})"
                            wire:confirm="Yakin ingin menghapus transaksi {{ $transaksi->invoice }} ini?" spinner
                            class="btn-ghost btn-sm text-red-500" />
                    @endif
                    @if (Auth::user()->role_id == 1 ||
                            (Carbon::parse($transaksi->created_at)->isSameDay($this->today) &&
                                $transaksi->user_id == Auth::user()->id &&
                                $transaksi->status == 'Perbaikan'))
                        <x-button icon="o-pencil"
                            link="/obat-keluar/{{ $transaksi->id }}/edit?invoice={{ $transaksi->invoice }}"
                            class="btn-ghost btn-sm text-yellow-500" />
                    @endif
                    @if ($transaksi->status == 'Perbaikan')
                        <x-button icon="o-pencil-square" wire:click="openStatusModal({{ $transaksi->id }})" spinner
                            class="btn-ghost btn-sm text-purple-500" tooltip="Update Status" />
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

            <x-choices-offline placeholder="Pilih Client" wire:model.live="client_id" :options="$client" icon="o-user"
                single searchable />

            <x-choices-offline placeholder="Pilih Barang" wire:model.live="barang_id" :options="$barang" icon="o-flag"
                single searchable />

            <x-select placeholder="Pilih Peternak" wire:model.live="tipePeternak" :options="$tipePeternakOptions" icon="o-tag"
                placeholder-value="0" />

            <!-- ✅ Tambahkan Filter Tanggal -->
            <x-input label="Tanggal Awal" type="date" wire:model.live="startDate" />
            <x-input label="Tanggal Akhir" type="date" wire:model.live="endDate" />
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

    <!-- ✅ MODAL UBAH STATUS -->
    <x-modal wire:model="statusModal" title="Ubah Status Transaksi" separator>
        <div class="space-y-4">

            <x-input label="Invoice" value="{{ $selectedInv ?: '-' }}" readonly />
            <x-select label="Status Baru" placeholder="Pilih Status" wire:model="status" :options="[
                ['id' => 'Perbaikan', 'name' => 'Perbaikan'],
                ['id' => 'Selesai', 'name' => 'Selesai'],
                ['id' => 'Batal', 'name' => 'Batal'],
            ]" />
        </div>

        <x-slot:actions>
            <x-button label="Batal" @click="$wire.statusModal=false" />
            <x-button label="Simpan" class="btn-primary" wire:click="updateStatus" spinner />
        </x-slot:actions>
    </x-modal>
</div>
