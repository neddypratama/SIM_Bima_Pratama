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
