<?php

use App\Models\Stok;
use App\Models\StokBatch;
use App\Models\StokKeluarBatch;
use App\Models\Barang;
use App\Models\Transaksi;
use App\Models\Kategori;
use App\Models\DetailTransaksi;
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

        return Excel::download(new StokObatExport($this->startDate, $this->endDate), 'stok-obat.xlsx');
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
        DB::transaction(function () {
            $stok = Stok::findOrFail($this->selectedId);
            $barang = Barang::findOrFail($stok->barang_id);
            if ($this->status == 'Selesai') {
                $stok->update(['status' => 'Selesai']);

                $str = substr($stok->invoice, -4);
                $part = explode('-', $stok->invoice);
                $tanggal = $part[1];

                $invoice1 = 'INV-' . $tanggal . '-RTN-' . $str;
                $invoice2 = 'INV-' . $tanggal . '-KDL-' . $str;
                $invoice3 = 'INV-' . $tanggal . '-OBT1-' . $str;
                $invoice4 = 'INV-' . $tanggal . '-OBT2-' . $str;
                $invoice5 = 'INV-' . $tanggal . '-TBH-' . $str;
                $invoice6 = 'INV-' . $tanggal . '-OBT3-' . $str;
                $invoice7 = 'INV-' . $tanggal . '-KRG-' . $str;
                $invoice8 = 'INV-' . $tanggal . '-OBT4-' . $str;

                $kateKotor = Kategori::where('name', 'like', '%Stok Return%')->first();
                $katePecah = Kategori::where('name', 'like', '%Barang Kadaluarsa%')->first();
                $kateTelur = Kategori::where('name', 'like', '%Stok Obat-Obatan%')->first();
                $kateStok = Kategori::where('name', 'like', '%Penyesuaian Stok')->first();

                if ($stok->tambah > 0) {
                    $harga = StokBatch::where('barang_id', $stok->barang_id)->latest('tanggal')->value('harga') ?? 0;

                    $trx = Transaksi::create([
                        'invoice' => $invoice5,
                        'name' => 'Obat Tambah ' . $barang->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Debit',
                        'total' => $harga * $stok->tambah,
                    ]);

                    $detail = DetailTransaksi::create([
                        'transaksi_id' => $trx->id,
                        'kategori_id' => $kateStok->id,
                        'barang_id' => $stok->barang_id,
                        'value' => $harga,
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
                        'name' => 'Obat Tambah ' . Barang::find($stok->barang_id)->name,
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

                /* =========================
                KURANG STOK (FIFO)
                ========================== */
                if ($stok->kurang > 0) {
                    $trx = Transaksi::create([
                        'invoice' => $invoice7,
                        'name' => 'Obat Kurang ' . $barang->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Kredit',
                        'total' => 0,
                    ]);

                    $detail = DetailTransaksi::create([
                        'transaksi_id' => $trx->id,
                        'kategori_id' => $kateStok->id,
                        'barang_id' => $stok->barang_id,
                        'value' => 0,
                        'kuantitas' => $stok->kurang,
                        'sub_total' => 0,
                    ]);

                    $hpp = $this->kurangiStokFifoDanHitungHpp($stok->barang_id, $stok->kurang, $detail->id);

                    $detail->update([
                        'value' => $hpp / $stok->kurang,
                        'sub_total' => $hpp,
                    ]);

                    $trx->update(['total' => $hpp]);

                    // Pakan Kadaluarsa - Kredit
                    $telur2 = Transaksi::create([
                        'invoice' => $invoice8,
                        'name' => 'Obat Kurang ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Debit',
                        'total' => $hpp,
                    ]);

                    DetailTransaksi::create([
                        'transaksi_id' => $telur2->id,
                        'kategori_id' => $kateTelur->id ?? null,
                        'value' => $hpp / $stok->kurang,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->kurang,
                        'sub_total' => $hpp,
                    ]);
                }

                /* =========================
                KOTOR
                ========================== */
                if ($stok->kotor > 0) {
                    // keluar FIFO
                    $trx = Transaksi::create([
                        'invoice' => $invoice1,
                        'name' => 'Obat Return ' . $barang->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Debit',
                        'total' => 0,
                    ]);

                    $detail = DetailTransaksi::create([
                        'transaksi_id' => $trx->id,
                        'kategori_id' => $kateKotor->id,
                        'barang_id' => $stok->barang_id,
                        'value' => 0,
                        'kuantitas' => $stok->kotor,
                        'sub_total' => 0,
                    ]);

                    $hpp = $this->kurangiStokFifoDanHitungHpp($stok->barang_id, $stok->kotor, $detail->id);

                    $detail->update([
                        'value' => $hpp / $stok->kotor,
                        'sub_total' => $hpp,
                    ]);

                    $trx->update(['total' => $hpp]);

                    // Pakan Return - Kredit
                    $telur1 = Transaksi::create([
                        'invoice' => $invoice3,
                        'name' => 'Obat Return ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Kredit',
                        'total' => $hpp,
                    ]);

                    DetailTransaksi::create([
                        'transaksi_id' => $telur1->id,
                        'kategori_id' => $kateTelur->id ?? null,
                        'value' => $hpp / $stok->kotor,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->kotor,
                        'sub_total' => $hpp,
                    ]);
                } elseif ($stok->kotor < 0) {
                    // ✅ MASUK (buat batch)
                    $qty = abs($stok->kotor);

                    $harga = StokBatch::where('barang_id', $stok->barang_id)->latest('tanggal')->value('harga') ?? 0;

                    $trx = Transaksi::create([
                        'invoice' => $invoice1,
                        'name' => 'Obat Return ' . $barang->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Kredit',
                        'total' => $harga * $qty,
                    ]);

                    $detail = DetailTransaksi::create([
                        'transaksi_id' => $trx->id,
                        'kategori_id' => $kateKotor->id,
                        'barang_id' => $stok->barang_id,
                        'value' => $harga,
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
                        'name' => 'Obat Return ' . Barang::find($stok->barang_id)->name,
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

                /* =========================
                PECAH
                ========================== */
                if ($stok->rusak > 0) {
                    $trx = Transaksi::create([
                        'invoice' => $invoice2,
                        'name' => ' Obat Kadaluarsa ' . $barang->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Debit',
                        'total' => 0,
                    ]);

                    $detail = DetailTransaksi::create([
                        'transaksi_id' => $trx->id,
                        'kategori_id' => $katePecah->id,
                        'barang_id' => $stok->barang_id,
                        'value' => 0,
                        'kuantitas' => $stok->rusak,
                        'sub_total' => 0,
                    ]);

                    $hpp = $this->kurangiStokFifoDanHitungHpp($stok->barang_id, $stok->rusak, $detail->id);

                    $detail->update([
                        'value' => $hpp / $stok->rusak,
                        'sub_total' => $hpp,
                    ]);

                    $trx->update(['total' => $hpp]);

                    // Pakan Kadaluarsa - Kredit
                    $telur2 = Transaksi::create([
                        'invoice' => $invoice4,
                        'name' => 'Obat Kadaluarsa ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Kredit',
                        'total' => $hpp,
                    ]);

                    DetailTransaksi::create([
                        'transaksi_id' => $telur2->id,
                        'kategori_id' => $kateTelur->id ?? null,
                        'value' => $hpp / $stok->rusak,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->rusak,
                        'sub_total' => $hpp,
                    ]);
                }

                $this->success("Status stok {$stok->invoice} berhasil diubah menjadi Selesai", position: 'toast-top');
            } else {
                $stok->update(['status' => 'Batal']);
                $this->success("Status stok {$stok->invoice} berhasil diubah menjadi Batal", position: 'toast-top');
            }
        });

        $this->statusModal = false;
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
                            link="/stok-obat/{{ $transaksi->id }}/edit?invoice={{ $transaksi->invoice }}"
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
