<?php

use App\Models\Stok;
use App\Models\StokBatch;
use App\Models\StokKeluarBatch;
use App\Models\Barang;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Kategori;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exports\StokPakanExport;
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
    public int $barang_id = 0;

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
    public function clear(): void
    {
        $this->reset(['search', 'barang_id', 'filter', 'startDate', 'endDate']);
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

        return Excel::download(new StokPakanExport($this->startDate, $this->endDate), 'stok-pakan.xlsx');
    }

    public function delete($id): void
    {
        DB::transaction(function () use ($id) {
            $stok = Stok::findOrFail($id);
            $inv = substr($stok->invoice, -4);
            $tgl = explode('-', $stok->invoice)[1];

            /* =========================
         1️⃣ ROLLBACK FIFO STOK
        ========================== */
            // rollback stok masuk lama
            if ($stok->tambah > 0) {
                $this->fifo($stok->barang_id, $stok->tambah, 'out');
            }

            // rollback stok keluar lama
            if ($stok->kurang > 0) {
                $this->fifo($stok->barang_id, $stok->kurang, 'in');
            }

            if ($stok->kotor > 0) {
                $this->fifo($stok->barang_id, $stok->kotor, 'in');
            }

            if ($stok->kotor < 0) {
                $this->fifo($stok->barang_id, abs($stok->kotor), 'out');
            }

            if ($stok->rusak > 0) {
                $this->fifo($stok->barang_id, $stok->rusak, 'in');
            }

            /* =========================
         2️⃣ HAPUS TRANSAKSI TURUNAN
        ========================== */
            $transaksis = Transaksi::where('invoice', 'like', "INV-$tgl-%-$inv")->get();

            foreach ($transaksis as $trx) {
                $trx->details()->delete();
                $trx->delete();
            }

            /* =========================
         3️⃣ HAPUS STOK UTAMA
        ========================== */
            $stok->delete();
        });

        $this->warning('Stok berhasil dihapus & stok dikembalikan', position: 'toast-top');
    }

    private function kurangiStokFifoDanHitungHpp(int $barangId, float $qtyKeluar, int $detailId): float
    {
        $totalHpp = 0;

        $batches = StokBatch::where('barang_id', $barangId)->where('qty_sisa', '>', 0)->orderBy('tanggal')->lockForUpdate()->get();

        foreach ($batches as $batch) {
            if ($qtyKeluar <= 0) {
                break;
            }

            $ambil = min($batch->qty_sisa, $qtyKeluar);

            $batch->decrement('qty_sisa', $ambil);

            // ✅ SIMPAN FIFO KELUAR
            StokKeluarBatch::create([
                'detail_transaksi_id' => $detailId,
                'stok_batch_id' => $batch->id,
                'qty' => $ambil,
                'returned_qty' => 0,
                'harga' => $batch->harga,
            ]);

            $totalHpp += $ambil * $batch->harga;
            $qtyKeluar -= $ambil;
        }

        return $totalHpp;
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
        $stok = Stok::findOrFail($this->selectedId);
        $barang = Barang::findOrFail($stok->barang_id);
        if ($this->status == 'Selesai') {
            $stok->update(['status' => 'Selesai']);

            $str = substr($stok->invoice, -4);
            $part = explode('-', $stok->invoice);
            $tanggal = $part[1];

            $invoice1 = 'INV-' . $tanggal . '-RTN-' . $str;
            $invoice2 = 'INV-' . $tanggal . '-KDL-' . $str;
            $invoice3 = 'INV-' . $tanggal . '-STR1-' . $str;
            $invoice4 = 'INV-' . $tanggal . '-STR2-' . $str;
            $invoice5 = 'INV-' . $tanggal . '-TBH-' . $str;
            $invoice6 = 'INV-' . $tanggal . '-STR3-' . $str;
            $invoice7 = 'INV-' . $tanggal . '-KRG-' . $str;
            $invoice8 = 'INV-' . $tanggal . '-STR4-' . $str;

            $kateKotor = Kategori::where('name', 'like', '%Stok Return%')->first();
            $katePecah = Kategori::where('name', 'like', '%Barang Kadaluarsa%')->first();
            $kateTelur = Kategori::where('name', 'like', '%Stok Pakan%')->first();
            $kateStok = Kategori::where('name', 'like', '%Penyesuaian Stok')->first();

            if ($stok->tambah > 0) {
                $harga = StokBatch::where('barang_id', $stok->barang_id)->latest('tanggal')->value('harga') ?? 0;

                $tambah = Transaksi::create([
                    'invoice' => $invoice5,
                    'name' => 'Pakan Tambah ' . Barang::find($stok->barang_id)->name,
                    'user_id' => $stok->user_id,
                    'tanggal' => $stok->tanggal,
                    'type' => 'Debit',
                    'total' => $harga * $stok->tambah,
                ]);

                $detail = DetailTransaksi::create([
                    'transaksi_id' => $tambah->id,
                    'kategori_id' => $kateStok->id ?? null,
                    'value' => $harga,
                    'barang_id' => $stok->barang_id,
                    'kuantitas' => $stok->tambah,
                    'sub_total' => $harga * $stok->tambah,
                ]);

                // ✅ BUAT BATCH
                StokBatch::create([
                    'barang_id' => $stok->barang_id,
                    'detail_transaksi_id' => $detail->id,
                    'user_id' => $stok->user_id,
                    'qty_masuk' => $stok->tambah,
                    'qty_sisa' => $stok->tambah,
                    'harga' => $harga,
                    'tanggal' => $stok->tanggal,
                ]);

                // Pakan Kadaluarsa - Kredit
                $telur2 = Transaksi::create([
                    'invoice' => $invoice6,
                    'name' => 'Pakan Tambah ' . Barang::find($stok->barang_id)->name,
                    'user_id' => $stok->user_id,
                    'tanggal' => $stok->tanggal,
                    'type' => 'Kredit',
                    'total' => $harga * $stok->tambah,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $telur2->id,
                    'kategori_id' => $kateTelur->id ?? null,
                    'value' => $harga,
                    'barang_id' => $stok->barang_id,
                    'kuantitas' => $stok->tambah,
                    'sub_total' => $harga * $stok->tambah,
                ]);
            }

            if ($stok->kurang > 0) {
                $kurang = Transaksi::create([
                    'invoice' => $invoice7,
                    'name' => 'Pakan Kurang ' . Barang::find($stok->barang_id)->name,
                    'user_id' => $stok->user_id,
                    'tanggal' => $stok->tanggal,
                    'type' => 'Kredit',
                    'total' => 0,
                ]);

                $detail = DetailTransaksi::create([
                    'transaksi_id' => $kurang->id,
                    'kategori_id' => $kateStok->id ?? null,
                    'value' => 0,
                    'barang_id' => $stok->barang_id,
                    'kuantitas' => $stok->kurang,
                    'sub_total' => 0,
                ]);

                $hppKurang = $this->kurangiStokFifoDanHitungHpp($stok->barang_id, $stok->kurang, $detail->id);

                $detail->update([
                    'value' => $hppKurang / $stok->kurang,
                    'sub_total' => $hppKurang,
                ]);

                $kurang->update(['total' => $hppKurang]);

                // Pakan Kadaluarsa - Kredit
                $telur2 = Transaksi::create([
                    'invoice' => $invoice8,
                    'name' => 'Pakan Kurang ' . Barang::find($stok->barang_id)->name,
                    'user_id' => $stok->user_id,
                    'tanggal' => $stok->tanggal,
                    'type' => 'Debit',
                    'total' => $hppKurang,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $telur2->id,
                    'kategori_id' => $kateTelur->id ?? null,
                    'value' => $hppKurang / $stok->kurang,
                    'barang_id' => $stok->barang_id,
                    'kuantitas' => $stok->kurang,
                    'sub_total' => $hppKurang,
                ]);
            }

            if ($stok->kotor > 0) {
                // TELUR KOTOR - Debit
                $kotor = Transaksi::create([
                    'invoice' => $invoice1,
                    'name' => 'Pakan Return ' . Barang::find($stok->barang_id)->name,
                    'user_id' => $stok->user_id,
                    'tanggal' => $stok->tanggal,
                    'type' => 'Debit',
                    'total' => 0,
                ]);

                $detail = DetailTransaksi::create([
                    'transaksi_id' => $kotor->id,
                    'kategori_id' => $kateKotor->id ?? null,
                    'value' => 0,
                    'barang_id' => $stok->barang_id,
                    'kuantitas' => $stok->kotor,
                    'sub_total' => 0,
                ]);

                $hppKotor = $this->kurangiStokFifoDanHitungHpp($stok->barang_id, $stok->kurang, $detail->id);

                $detail->update([
                    'value' => $hppKotor / $stok->kurang,
                    'sub_total' => $hppKotor,
                ]);

                $kurang->update(['total' => $hppKotor]);

                // Pakan Return - Kredit
                $telur1 = Transaksi::create([
                    'invoice' => $invoice3,
                    'name' => 'Pakan Return ' . Barang::find($stok->barang_id)->name,
                    'user_id' => $stok->user_id,
                    'tanggal' => $stok->tanggal,
                    'type' => 'Kredit',
                    'total' => $hppKotor,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $telur1->id,
                    'kategori_id' => $kateTelur->id ?? null,
                    'value' => $hppKotor / $stok->kotor,
                    'barang_id' => $stok->barang_id,
                    'kuantitas' => $stok->kotor,
                    'sub_total' => $hppKotor,
                ]);
            } elseif ($stok->kotor < 0) {
                // ✅ MASUK (buat batch)
                $qty = abs($stok->kotor);
                $harga = StokBatch::where('barang_id', $stok->barang_id)->latest('tanggal')->value('harga') ?? 0;

                // Pakan Return - Debit
                $kotor = Transaksi::create([
                    'invoice' => $invoice1,
                    'name' => 'Pakan Return ' . Barang::find($stok->barang_id)->name,
                    'user_id' => $stok->user_id,
                    'tanggal' => $stok->tanggal,
                    'type' => 'Kredit',
                    'total' => $harga * $qty,
                ]);

                $detail = DetailTransaksi::create([
                    'transaksi_id' => $kotor->id,
                    'kategori_id' => $kateKotor->id ?? null,
                    'value' => $harga,
                    'barang_id' => $stok->barang_id,
                    'kuantitas' => $qty,
                    'sub_total' => $harga * $qty,
                ]);

                // ✅ WAJIB: BUAT BATCH BARU
                StokBatch::create([
                    'barang_id' => $stok->barang_id,
                    'detail_transaksi_id' => $detail->id,
                    'user_id' => $stok->user_id,
                    'qty_masuk' => $qty,
                    'qty_sisa' => $qty,
                    'harga' => $harga,
                    'tanggal' => $stok->tanggal,
                ]);

                // Pakan Return - Kredit
                $telur1 = Transaksi::create([
                    'invoice' => $invoice3,
                    'name' => 'Pakan Return ' . Barang::find($stok->barang_id)->name,
                    'user_id' => $stok->user_id,
                    'tanggal' => $stok->tanggal,
                    'type' => 'Debit',
                    'total' => $harga * $qty,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $telur1->id,
                    'kategori_id' => $kateTelur->id ?? null,
                    'value' => $harga,
                    'barang_id' => $stok->barang_id,
                    'kuantitas' => $qty,
                    'sub_total' => $harga * $qty,
                ]);
            }

            // TELUR PECAH - Debit
            if ($stok->pecah > 0) {
                $pecah = Transaksi::create([
                    'invoice' => $invoice2,
                    'name' => 'Pakan Kadaluarsa ' . Barang::find($stok->barang_id)->name,
                    'user_id' => $stok->user_id,
                    'tanggal' => $stok->tanggal,
                    'type' => 'Debit',
                    'total' => 0,
                ]);

                $detail = DetailTransaksi::create([
                    'transaksi_id' => $pecah->id,
                    'kategori_id' => $katePecah->id ?? null,
                    'value' => 0,
                    'barang_id' => $stok->barang_id,
                    'kuantitas' => $stok->pecah,
                    'sub_total' => 0,
                ]);

                $hppPecah = $this->kurangiStokFifoDanHitungHpp($stok->barang_id, $stok->pecah, $detail->id);

                $detail->update([
                    'value' => $hppPecah / $stok->pecah,
                    'sub_total' => $hppPecah,
                ]);

                $pecah->update(['total' => $hppPecah]);

                // Pakan Kadaluarsa - Kredit
                $telur2 = Transaksi::create([
                    'invoice' => $invoice4,
                    'name' => 'Pakan Kadaluarsa ' . Barang::find($stok->barang_id)->name,
                    'user_id' => $stok->user_id,
                    'tanggal' => $stok->tanggal,
                    'type' => 'Kredit',
                    'total' => $hppPecah,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $telur2->id,
                    'kategori_id' => $kateTelur->id ?? null,
                    'value' => $hppPecah / $stok->pecah,
                    'barang_id' => $stok->barang_id,
                    'kuantitas' => $stok->pecah,
                    'sub_total' => $hppPecah,
                ]);
            }
            $this->success("Status stok {$stok->invoice} berhasil diubah menjadi Selesai", position: 'toast-top');
        } else {
            $stok->update(['status' => 'Batal']);
            $this->success("Status stok {$stok->invoice} berhasil diubah menjadi Batal", position: 'toast-top');
        }
    }

    public function headers(): array
    {
        return [['key' => 'invoice', 'label' => 'Invoice', 'class' => 'w-36'], ['key' => 'barang.name', 'label' => 'Barang', 'class' => 'w-36'], ['key' => 'tanggal', 'label' => 'Tanggal', 'class' => 'w-16'], ['key' => 'user.name', 'label' => 'Pembuat', 'class' => 'w-16'], ['key' => 'tambah', 'label' => ' Tambah', 'class' => 'w-16'], ['key' => 'kurang', 'label' => ' Kurang', 'class' => 'w-16'], ['key' => 'kotor', 'label' => ' Return', 'class' => 'w-16'], ['key' => 'rusak', 'label' => ' Kadaluarsa', 'class' => 'w-16'], ['key' => 'status', 'label' => 'Status', 'class' => 'w-16']];
    }

    public function transaksi(): LengthAwarePaginator
    {
        return Stok::query()
            ->with(['barang:id,name', 'user:id,name'])
            ->whereHas('barang.jenis', function ($q) {
                $q->where('name', 'like', 'Pakan%');
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
            ->when($this->startDate, fn(Builder $q) => $q->whereDate('tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn(Builder $q) => $q->whereDate('tanggal', '<=', $this->endDate))
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
            if ($this->startDate != null) {
                $this->filter++;
            }
        }
        return [
            'transaksi' => $this->transaksi(),
            'barang' => Barang::whereHas('jenis', function ($q) {
                $q->where('name', 'like', '%Pakan%');
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
    <x-header title="Transaksi Stok Pakan" separator progress-indicator>
        <x-slot:actions>
            <div class="flex flex-row sm:flex-row gap-2">
                <x-button wire:click="openExportModal" icon="fas.download" primary>Export Excel</x-button>
                <x-button label="Create" link="/stok-pakan/create" responsive icon="o-plus" class="btn-primary" />
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
            link="stok-pakan/{id}/show?barang={barang.name}">
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
                    @if (Auth::user()->role_id == 1 ||
                            (Carbon::parse($transaksi->tanggal)->isSameDay($this->today) && $transaksi->user_id == Auth::user()->id))
                        <x-button icon="o-pencil"
                            link="/stok-pakan/{{ $transaksi->id }}/edit?invoice={{ $transaksi->invoice }}"
                            class="btn-ghost btn-sm text-yellow-500" />
                    @endif
                    @if (Auth::user()->role_id == 1)
                        <x-button icon="o-trash" wire:click="delete({{ $transaksi->id }})"
                            wire:confirm="Yakin ingin menghapus transaksi {{ $transaksi->invoice }} ini?" spinner
                            class="btn-ghost btn-sm text-red-500" />
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

            <x-choices-offline placeholder="Pilih Barang" wire:model.live="barang_id" :options="$barang" icon="o-flag"
                single searchable />

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
