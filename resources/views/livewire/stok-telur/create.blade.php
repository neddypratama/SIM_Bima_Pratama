<?php

use Livewire\Volt\Component;
use App\Models\Barang;
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
        $this->tanggal = now()->format('Y-m-d\TH:i');
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
            $this->invoice5 = 'INV-' . $tanggal . '-TLR1-' . $str;
            $this->invoice6 = 'INV-' . $tanggal . '-TLR2-' . $str;
            $this->invoice7 = 'INV-' . $tanggal . '-TLR3-' . $str;
            $this->invoice8 = 'INV-' . $tanggal . '-TLR4-' . $str;
        }
    }

    public function updatedBarangId($id): void
    {
        if ($id) {
            $barang = Barang::find($id);
            $this->stok = $barang?->stok ?? 0;
            $this->awal = $barang?->stok ?? 0;
        }
    }

    public function updated($field): void
    {
        if (in_array($field, ['tambah', 'kurang', 'kotor', 'bentes', 'ceplok', 'prok', 'rusak'])) {
            $barang = Barang::find($this->barang_id);
            if ($barang) {
                $stok_awal = $barang->stok;
                $stok_baru = $stok_awal + $this->tambah - ($this->kurang + $this->kotor + $this->bentes + $this->ceplok + $this->prok);
                $this->stok = max(0, $stok_baru);
            }
        }
    }

    public function save(): void
    {
        $this->validate();

        $barang = Barang::find($this->barang_id);
        if (!$barang) {
            $this->error('Barang tidak ditemukan.');
            return;
        }

        $barang->update(['stok' => $this->stok]);

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
        ]);

        $kateKotor = Kategori::where('name', 'like', '%Telur Kotor%')->first();
        $kateProk = Kategori::where('name', 'like', '%Telur Prok%')->first();
        $kateBentes = Kategori::where('name', 'like', '%Telur Bentes%')->first();
        $kateCeplok = Kategori::where('name', 'like', '%Telur Ceplok%')->first();
        $kateTelur = Kategori::where('name', 'like', '%Stok Telur%')->first();

        if ($this->kotor > 0) {
            // TELUR KOTOR - Debit
            $kotor = Transaksi::create([
                'invoice' => $this->invoice1,
                'name' => 'Telur Kotor ' . $barang->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'type' => 'Debit',
                'total' => ($barang->hpp ?? 0) * ($this->kotor ?? 0),
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $kotor->id,
                'kategori_id' => $kateKotor->id ?? null,
                'value' => $barang->hpp,
                'barang_id' => $barang->id,
                'kuantitas' => $this->kotor,
                'sub_total' => ($barang->hpp ?? 0) * ($this->kotor ?? 0),
            ]);

            // TELUR KOTOR - Kredit
            $telur1 = Transaksi::create([
                'invoice' => $this->invoice5,
                'name' => 'Telur Kotor ' . $barang->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'type' => 'Kredit',
                'total' => ($barang->hpp ?? 0) * ($this->kotor ?? 0),
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $telur1->id,
                'kategori_id' => $kateTelur->id ?? null,
                'value' => $barang->hpp,
                'barang_id' => $barang->id,
                'kuantitas' => $this->kotor,
                'sub_total' => ($barang->hpp ?? 0) * ($this->kotor ?? 0),
            ]);
        } else {
            // TELUR KOTOR - Debit
            $kotor = Transaksi::create([
                'invoice' => $this->invoice1,
                'name' => 'Telur Kotor ' . $barang->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'type' => 'Kredit',
                'total' => ($barang->hpp ?? 0) * ($this->kotor ?? 0) * -1,
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $kotor->id,
                'kategori_id' => $kateKotor->id ?? null,
                'value' => $barang->hpp,
                'barang_id' => $barang->id,
                'kuantitas' => $this->kotor * -1,
                'sub_total' => ($barang->hpp ?? 0) * ($this->kotor ?? 0) * -1,
            ]);

            // TELUR KOTOR - Kredit
            $telur1 = Transaksi::create([
                'invoice' => $this->invoice5,
                'name' => 'Telur Kotor ' . $barang->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'type' => 'Debit',
                'total' => ($barang->hpp ?? 0) * ($this->kotor ?? 0) * -1,
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $telur1->id,
                'kategori_id' => $kateTelur->id ?? null,
                'value' => $barang->hpp,
                'barang_id' => $barang->id,
                'kuantitas' => $this->kotor * -1,
                'sub_total' => ($barang->hpp ?? 0) * ($this->kotor ?? 0) * -1,
            ]);
        }

        // TELUR BENTES - Debit
        $bentes = Transaksi::create([
            'invoice' => $this->invoice2,
            'name' => 'Telur Bentes ' . $barang->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'type' => 'Debit',
            'total' => ($barang->hpp ?? 0) * ($this->bentes ?? 0),
        ]);

        DetailTransaksi::create([
            'transaksi_id' => $bentes->id,
            'kategori_id' => $kateBentes->id ?? null,
            'value' => $barang->hpp,
            'barang_id' => $barang->id,
            'kuantitas' => $this->bentes,
            'sub_total' => ($barang->hpp ?? 0) * ($this->bentes ?? 0),
        ]);

        // TELUR BENTES - Kredit
        $telur2 = Transaksi::create([
            'invoice' => $this->invoice6,
            'name' => 'Telur Bentes ' . $barang->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'type' => 'Kredit',
            'total' => ($barang->hpp ?? 0) * ($this->bentes ?? 0),
        ]);

        DetailTransaksi::create([
            'transaksi_id' => $telur2->id,
            'kategori_id' => $kateTelur->id ?? null,
            'value' => $barang->hpp,
            'barang_id' => $barang->id,
            'kuantitas' => $this->bentes,
            'sub_total' => ($barang->hpp ?? 0) * ($this->bentes ?? 0),
        ]);

        // TELUR CEPLOK - Debit
        $ceplok = Transaksi::create([
            'invoice' => $this->invoice3,
            'name' => 'Telur Ceplok ' . $barang->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'type' => 'Debit',
            'total' => ($barang->hpp ?? 0) * ($this->ceplok ?? 0),
        ]);

        DetailTransaksi::create([
            'transaksi_id' => $ceplok->id,
            'kategori_id' => $kateCeplok->id ?? null,
            'value' => $barang->hpp,
            'barang_id' => $barang->id,
            'kuantitas' => $this->ceplok,
            'sub_total' => ($barang->hpp ?? 0) * ($this->ceplok ?? 0),
        ]);

        // TELUR CEPLOK - Kredit
        $telur3 = Transaksi::create([
            'invoice' => $this->invoice7,
            'name' => 'Telur Ceplok ' . $barang->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'type' => 'Kredit',
            'total' => ($barang->hpp ?? 0) * ($this->ceplok ?? 0),
        ]);

        DetailTransaksi::create([
            'transaksi_id' => $telur3->id,
            'kategori_id' => $kateTelur->id ?? null,
            'value' => $barang->hpp,
            'barang_id' => $barang->id,
            'kuantitas' => $this->ceplok,
            'sub_total' => ($barang->hpp ?? 0) * ($this->ceplok ?? 0),
        ]);

        // TELUR PROK - Debit
        $prok = Transaksi::create([
            'invoice' => $this->invoice4,
            'name' => 'Telur Prok ' . $barang->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'type' => 'Debit',
            'total' => ($barang->hpp ?? 0) * ($this->prok ?? 0),
        ]);

        DetailTransaksi::create([
            'transaksi_id' => $prok->id,
            'kategori_id' => $kateProk->id ?? null,
            'value' => $barang->hpp,
            'barang_id' => $barang->id,
            'kuantitas' => $this->prok,
            'sub_total' => ($barang->hpp ?? 0) * ($this->prok ?? 0),
        ]);

        // TELUR PROK - Kredit
        $telur4 = Transaksi::create([
            'invoice' => $this->invoice8,
            'name' => 'Telur Prok ' . $barang->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'type' => 'Kredit',
            'total' => ($barang->hpp ?? 0) * ($this->prok ?? 0),
        ]);

        DetailTransaksi::create([
            'transaksi_id' => $telur4->id,
            'kategori_id' => $kateTelur->id ?? null,
            'value' => $barang->hpp,
            'barang_id' => $barang->id,
            'kuantitas' => $this->prok,
            'sub_total' => ($barang->hpp ?? 0) * ($this->prok ?? 0),
        ]);

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
                        <x-datetime label="Date + Time" wire:model="tanggal" icon="o-calendar" type="datetime-local" />
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
                        <x-input label="Telur Bertambah" wire:model.lazy="tambah" type="number" step="0.01" min="0" />
                        <x-input label="Telur Berkurang" wire:model.lazy="kurang" type="number" step="0.01" min="0" />
                        <x-input label="Telur Kotor" wire:model.lazy="kotor" type="number" step="0.01" />
                        <x-input label="Telur Bentes" wire:model.lazy="bentes" type="number" step="0.01" min="0" />
                        <x-input label="Telur Ceplok" wire:model.lazy="ceplok" type="number" step="0.01" min="0" />
                        <x-input label="Telur Prok" wire:model.lazy="prok" type="number" step="0.01" min="0" />
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
