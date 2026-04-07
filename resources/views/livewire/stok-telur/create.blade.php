<?php

use Livewire\Volt\Component;
use App\Models\Barang;
use App\Models\StokBatch;
use App\Models\StokKeluarBatch;
use App\Models\Kategori;
use App\Models\Stok;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use Toast, WithFileUploads;

    #[Rule('required|unique:transaksis,invoice')]
    public string $invoice = '';
    public string $invoice1 = '';
    public string $invoice2 = '';
    public string $invoice3 = '';
    public string $invoice4 = '';
    public string $invoice5 = '';
    public string $invoice6 = '';
    public string $invoice7 = '';
    public string $invoice8 = '';
    public string $invoice9 = '';
    public string $invoice10 = '';
    public string $invoice11 = '';
    public string $invoice12 = '';
    public string $invoice13 = '';
    public string $invoice14 = '';

    #[Rule('required')]
    public ?int $barang_id = null;

    public ?string $tanggal = null;
    public ?int $user_id = null;
    public float $stok = 0;
    public float $awal = 0;

    #[Rule('nullable|numeric|min:0')]
    public float $tambah = 0;

    #[Rule('nullable|numeric|min:0')]
    public float $kurang = 0;

    #[Rule('nullable|numeric')]
    public float $kotor = 0;

    #[Rule('nullable|numeric|min:0')]
    public float $bentes = 0;

    #[Rule('nullable|numeric|min:0')]
    public float $ceplok = 0;

    #[Rule('nullable|numeric|min:0')]
    public float $prok = 0;

    #[Rule('nullable|numeric|min:0')]
    public float $jumbo = 0;

    public function with(): array
    {
        return [
            'users' => User::all(),
            'barangs' => Barang::whereHas('jenis', function ($q) {
                $q->where('name', 'like', '%Telur%');
            })->get(),
        ];
    }

    public function mount(): void
    {
        $this->user_id = auth()->id();
        $this->tanggal = now()->format('Y-m-d\TH:i:s');
        $this->updatedTanggal($this->tanggal);
    }

    public function updatedTanggal($value): void
    {
        if ($value) {
            $tanggal = Carbon::parse($value)->format('Ymd');
            $str = Str::upper(Str::random(4));
            $this->invoice = 'INV-' . $tanggal . '-STK-' . $str;
            $this->invoice1 = 'INV-' . $tanggal . '-KTR-' . $str;
            $this->invoice2 = 'INV-' . $tanggal . '-BTS-' . $str;
            $this->invoice3 = 'INV-' . $tanggal . '-CLK-' . $str;
            $this->invoice4 = 'INV-' . $tanggal . '-PRK-' . $str;
            $this->invoice5 = 'INV-' . $tanggal . '-JMB-' . $str;
            $this->invoice6 = 'INV-' . $tanggal . '-TLR1-' . $str;
            $this->invoice7 = 'INV-' . $tanggal . '-TLR2-' . $str;
            $this->invoice8 = 'INV-' . $tanggal . '-TLR3-' . $str;
            $this->invoice9 = 'INV-' . $tanggal . '-TLR4-' . $str;
            $this->invoice10 = 'INV-' . $tanggal . '-TLR5-' . $str;
            $this->invoice11 = 'INV-' . $tanggal . '-TBH-' . $str;
            $this->invoice12 = 'INV-' . $tanggal . '-TLR6-' . $str;
            $this->invoice13 = 'INV-' . $tanggal . '-KRG-' . $str;
            $this->invoice14 = 'INV-' . $tanggal . '-TLR7-' . $str;
        }
    }

    public function updatedBarangId($id): void
    {
        if ($id) {
            $barang = StokBatch::where('barang_id', $id)->sum('qty_sisa');
            $this->stok = $barang ?? 0;
            $this->awal = $barang ?? 0;
        }
    }

    public function updated($field): void
    {
        if (in_array($field, ['tambah', 'kurang', 'kotor', 'bentes', 'ceplok', 'prok', 'rusak', 'jumbo'])) {
            $barang = StokBatch::where('barang_id', $this->barang_id)->sum('qty_sisa');
            if ($barang) {
                $stok_awal = $barang ?? 0;
                $stok_baru = $stok_awal + $this->tambah - ($this->kurang + $this->kotor + $this->bentes + $this->ceplok + $this->prok + $this->jumbo);
                $this->stok = max(0, $stok_baru);
            }
        }
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

    public function save(): void
    {$this->validate();

        DB::transaction(function () {
            $barang = Barang::find($this->barang_id);
            if (!$barang) {
                $this->error('Barang tidak ditemukan.');
                return;
            }

            /* =========================
                LOG STOK
            ========================== */

            Stok::create([
                'invoice' => $this->invoice,
                'user_id' => $this->user_id,
                'barang_id' => $this->barang_id,
                'tanggal' => $this->tanggal,
                'tambah' => $this->tambah,
                'kurang' => $this->kurang,
                'kotor' => $this->kotor,
                'bentes' => $this->bentes,
                'ceplok' => $this->ceplok,
                'rusak' => $this->prok,
                'jumbo' => $this->jumbo,
            ]);

            $kateKotor = Kategori::where('name', 'like', '%Telur Kotor%')->first();
            $kateProk = Kategori::where('name', 'like', '%Telur Prok%')->first();
            $kateBentes = Kategori::where('name', 'like', '%Telur Bentes%')->first();
            $kateCeplok = Kategori::where('name', 'like', '%Telur Ceplok%')->first();
            $kateTelur = Kategori::where('name', 'like', '%Stok Telur%')->first();
            $kateJumbo = Kategori::where('name', 'like', '%Telur Jumbo%')->first();
            $kateStok = Kategori::where('name', 'like', '%Penyesuaian Stok')->first();

            if ($this->tambah > 0) {
                $harga = StokBatch::where('barang_id', $this->barang_id)->latest('tanggal')->value('harga') ?? 0;
                $hppTambah = $harga * $this->tambah;
                $tambah = Transaksi::create([
                    'invoice' => $this->invoice11,
                    'name' => 'Telur Tambah ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Debit',
                    'total' => $hppTambah,
                ]);

                $detail = DetailTransaksi::create([
                    'transaksi_id' => $tambah->id,
                    'kategori_id' => $kateStok->id ?? null,
                    'value' => $harga,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $this->tambah,
                    'sub_total' => $harga * $this->tambah,
                ]);

                // ✅ BUAT BATCH
                StokBatch::create([
                    'barang_id' => $this->barang_id,
                    'detail_transaksi_id' => $detail->id,
                    'user_id' => $this->user_id,
                    'qty_masuk' => $this->tambah,
                    'qty_sisa' => $this->tambah,
                    'harga' => $harga,
                    'tanggal' => $this->tanggal,
                ]);

                // Telur Kadaluarsa - Kredit
                $telur2 = Transaksi::create([
                    'invoice' => $this->invoice12,
                    'name' => 'Telur Tambah ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Kredit',
                    'total' => $hppTambah,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $telur2->id,
                    'kategori_id' => $kateTelur->id ?? null,
                    'value' => $hppTambah / $this->tambah,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $this->tambah,
                    'sub_total' => $hppTambah,
                ]);
            }

            if ($this->kurang > 0) {
                $kurang = Transaksi::create([
                    'invoice' => $this->invoice13,
                    'name' => 'Telur Kurang ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Kredit',
                    'total' => 0,
                ]);

                 $detail = DetailTransaksi::create([
                    'transaksi_id' => $kurang->id,
                    'kategori_id' => $kateStok->id ?? null,
                    'value' => 0,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $this->kurang,
                    'sub_total' => 0,
                ]);

                $hppKurang = $this->kurangiStokFifoDanHitungHpp($this->barang_id, $this->kurang, $detail->id);

                $detail->update([
                    'value' => $hppKurang / $this->kurang,
                    'sub_total' => $hppKurang,
                ]);

                $kurang->update(['total' => $hppKurang]);

                // Telur Kadaluarsa - Kredit
                $telur2 = Transaksi::create([
                    'invoice' => $this->invoice14,
                    'name' => 'Telur Kurang ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Debit',
                    'total' => 0,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $telur2->id,
                    'kategori_id' => $kateTelur->id ?? null,
                    'value' => $hppKurang / $this->kurang,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $this->kurang,
                    'sub_total' => $hppKurang,
                ]);
            }

            if ($this->kotor > 0) {
                // TELUR KOTOR - Debit
                $kotor = Transaksi::create([
                    'invoice' => $this->invoice1,
                    'name' => 'Telur Kotor ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Debit',
                    'total' => 0,
                ]);

                $detail = DetailTransaksi::create([
                    'transaksi_id' => $kotor->id,
                    'kategori_id' => $kateKotor->id ?? null,
                    'value' => 0,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $this->kotor,
                    'sub_total' => 0,
                ]);

                $hppKotor = $this->kurangiStokFifoDanHitungHpp($this->barang_id, $this->kurang, $detail->id);

                $detail->update([
                    'value' => $hppKotor / $this->kurang,
                    'sub_total' => $hppKotor,
                ]);

                $kurang->update(['total' => $hppKotor]);

                // TELUR KOTOR - Kredit
                $telur1 = Transaksi::create([
                    'invoice' => $this->invoice6,
                    'name' => 'Telur Kotor ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Kredit',
                    'total' => $hppKotor,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $telur1->id,
                    'kategori_id' => $kateTelur->id ?? null,
                    'value' => $hppKotor / $this->kotor,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $this->kotor,
                    'sub_total' => $hppKotor,
                ]);
            } elseif ($this->kotor < 0) {
                $qty = abs($this->kotor);
                $harga = StokBatch::where('barang_id', $this->barang_id)->latest('tanggal')->value('harga') ?? 0;
                $hppKotor = $harga * $qty;

                // TELUR KOTOR - Debit
                $kotor = Transaksi::create([
                    'invoice' => $this->invoice1,
                    'name' => 'Telur Kotor ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Kredit',
                    'total' => $hppKotor,
                ]);

                $detail = DetailTransaksi::create([
                    'transaksi_id' => $kotor->id,
                    'kategori_id' => $kateKotor->id ?? null,
                    'value' => $hppKotor / $qty,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $qty,
                    'sub_total' => $hppKotor,
                ]);

                // ✅ WAJIB: BUAT BATCH BARU
                StokBatch::create([
                    'barang_id' => $this->barang_id,
                    'detail_transaksi_id' => $detail->id,
                    'user_id' => $this->user_id,
                    'qty_masuk' => $qty,
                    'qty_sisa' => $qty,
                    'harga' => $harga,
                    'tanggal' => $this->tanggal,
                ]);

                // TELUR KOTOR - Kredit
                $telur1 = Transaksi::create([
                    'invoice' => $this->invoice6,
                    'name' => 'Telur Kotor ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Debit',
                    'total' => $hppKotor,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $telur1->id,
                    'kategori_id' => $kateTelur->id ?? null,
                    'value' => $hppKotor / $qty,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $qty,
                    'sub_total' => $hppKotor,
                ]);
            }

            // TELUR BENTES - Debit
            if ($this->bentes > 0) {
                $bentes = Transaksi::create([
                    'invoice' => $this->invoice2,
                    'name' => 'Telur Bentes ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Debit',
                    'total' => 0,
                ]);

                $detail = DetailTransaksi::create([
                    'transaksi_id' => $bentes->id,
                    'kategori_id' => $kateBentes->id ?? null,
                    'value' => 0,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $this->bentes,
                    'sub_total' => 0,
                ]);

                $hppBentes = $this->kurangiStokFifoDanHitungHpp($this->barang_id, $this->bentes, $detail->id);

                $detail->update([
                    'value' => $hppBentes / $this->bentes,
                    'sub_total' => $hppBentes,
                ]);

                $bentes->update(['total' => $hppBentes]);

                // TELUR KOTOR - Kredit
                $telur1 = Transaksi::create([
                    'invoice' => $this->invoice7,
                    'name' => 'Telur Kotor ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Kredit',
                    'total' => $hppBentes,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $telur1->id,
                    'kategori_id' => $kateTelur->id ?? null,
                    'value' => $hppBentes / $this->bentes,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $this->bentes,
                    'sub_total' => $hppBentes,
                ]);
            }

            // TELUR CEPLOK - Debit
            if ($this->ceplok > 0) {
                $ceplok = Transaksi::create([
                    'invoice' => $this->invoice3,
                    'name' => 'Telur Ceplok ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Debit',
                    'total' => 0,
                ]);

                $detail = DetailTransaksi::create([
                    'transaksi_id' => $ceplok->id,
                    'kategori_id' => $kateCeplok->id ?? null,
                    'value' => 0,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $this->ceplok,
                    'sub_total' => 0,
                ]);

                $hppCeplok = $this->kurangiStokFifoDanHitungHpp($this->barang_id, $this->ceplok, $detail->id);

                $detail->update([
                    'value' => $hppCeplok / $this->ceplok,
                    'sub_total' => $hppCeplok,
                ]);

                $ceplok->update(['total' => $hppCeplok]);

                // TELUR KOTOR - Kredit
                $telur1 = Transaksi::create([
                    'invoice' => $this->invoice8,
                    'name' => 'Telur Ceplok ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Kredit',
                    'total' => $hppCeplok,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $telur1->id,
                    'kategori_id' => $kateTelur->id ?? null,
                    'value' => $hppCeplok / $this->ceplok,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $this->ceplok,
                    'sub_total' => $hppCeplok,
                ]);
            }

            // TELUR PROK - Debit
            if ($this->prok > 0) {
                $prok = Transaksi::create([
                    'invoice' => $this->invoice4,
                    'name' => 'Telur Prok ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Debit',
                    'total' => 0,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $prok->id,
                    'kategori_id' => $kateProk->id ?? null,
                    'value' => 0,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $this->prok,
                    'sub_total' => 0,
                ]);

                $hppProk = $this->kurangiStokFifoDanHitungHpp($this->barang_id, $this->prok, $detail->id);

                $detail->update([
                    'value' => $hppProk / $this->prok,
                    'sub_total' => $hppProk,
                ]);

                $prok->update(['total' => $hppProk]);

                // TELUR KOTOR - Kredit
                $telur1 = Transaksi::create([
                    'invoice' => $this->invoice9,
                    'name' => 'Telur Prok ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Kredit',
                    'total' => $hppProk,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $telur1->id,
                    'kategori_id' => $kateTelur->id ?? null,
                    'value' => $hppProk / $this->prok,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $this->prok,
                    'sub_total' => $hppProk,
                ]);
            }

            // TELUR JUMBO - Debit
            if ($this->jumbo > 0) {
                // TELUR KOTOR - Debit
                $jumbo = Transaksi::create([
                    'invoice' => $this->invoice5,
                    'name' => 'Telur Jumbo ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Debit',
                    'total' => 0,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $jumbo->id,
                    'kategori_id' => $kateJumbo->id ?? null,
                    'value' => 0,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $this->jumbo,
                    'sub_total' => 0,
                ]);

                $hppJumbo = $this->kurangiStokFifoDanHitungHpp($this->barang_id, $this->jumbo, $detail->id);

                $detail->update([
                    'value' => $hppJumbo / $this->jumbo,
                    'sub_total' => $hppJumbo,
                ]);

                $jumbo->update(['total' => $hppJumbo]);

                // TELUR KOTOR - Kredit
                $telur1 = Transaksi::create([
                    'invoice' => $this->invoice10,
                    'name' => 'Telur Jumbo ' . Barang::find($this->barang_id)->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'type' => 'Kredit',
                    'total' => $hppJumbo,
                ]);

                DetailTransaksi::create([
                    'transaksi_id' => $telur1->id,
                    'kategori_id' => $kateTelur->id ?? null,
                    'value' => $hppJumbo / $this->jumbo,
                    'barang_id' => $this->barang_id,
                    'kuantitas' => $this->jumbo,
                    'sub_total' => $hppJumbo,
                ]);
            }
        });

        $this->success('Stok berhasil diperbarui!', redirectTo: '/stok-telur');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Create Transaksi Stok Telur" separator progress-indicator />

    <x-form wire:submit="save">
        <!-- SECTION: Basic Info -->
        <x-card>
            <div class="lg:grid grid-cols-8 gap-4">
                <div class="col-span-2">
                    <x-header title="Basic Info" subtitle="Buat transaksi baru" size="text-2xl" />
                </div>
                <div class="col-span-6 grid gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input label="User" :value="auth()->user()->name" readonly />
                        <x-datetime label="Date + Time" wire:model="tanggal" icon="o-calendar" type="datetime-local"
                            step="1" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="col-span-2">
                            <x-choices-offline placeholder="Pilih Barang" wire:model.live="barang_id" :options="$barangs"
                                single searchable clearable label="Barang" />
                        </div>
                        <x-input label="Stok Awal" wire:model.live="awal" type="number" step="0.01" readonly />
                        <x-input label="Stok Sekarang" wire:model.live="stok" type="number" step="0.01" readonly />
                    </div>
                </div>
            </div>
        </x-card>

        <!-- SECTION: Detail Items -->
        <x-card>
            <div class="lg:grid grid-cols-8 gap-4">
                <div class="col-span-2">
                    <x-header title="Detail Items" subtitle="Tambah detail transaksi" size="text-2xl" />
                </div>
                <div class="col-span-6 grid gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end p-3 rounded-xl">
                        <x-input label="Telur Bertambah" wire:model.lazy="tambah" type="number" step="0.01"
                            min="0" />
                        <x-input label="Telur Berkurang" wire:model.lazy="kurang" type="number" step="0.01"
                            min="0" />
                        <x-input label="Telur Kotor" wire:model.lazy="kotor" type="number" step="0.01" />
                        <x-input label="Telur Bentes" wire:model.lazy="bentes" type="number" step="0.01"
                            min="0" />
                        <x-input label="Telur Ceplok" wire:model.lazy="ceplok" type="number" step="0.01"
                            min="0" />
                        <x-input label="Telur Prok" wire:model.lazy="prok" type="number" step="0.01"
                            min="0" />
                        <x-input label="Telur Jumbo" wire:model.lazy="jumbo" type="number" step="0.01"
                            min="0" />
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <div class="flex flex-row sm:flex-row gap-2 justify-end">
                <x-button spinner label="Cancel" link="/stok-telur" />
                <x-button spinner label="Create" icon="o-paper-airplane" spinner="save" type="submit"
                    class="btn-primary" />
            </div>
        </x-slot:actions>
    </x-form>
</div>
