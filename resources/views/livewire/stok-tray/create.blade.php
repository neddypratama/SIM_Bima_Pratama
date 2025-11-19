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

    #[Rule('nullable|numeric|min:0')]
    public float $pakai = 0;

    public function with(): array
    {
        return [
            'users' => User::all(),
            'barangs' => Barang::whereHas('jenis', function ($q) {
                $q->where('name', 'like', '%Tray%');
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
            $this->invoice1 = 'INV-' . $tanggal . '-PKI-' . $str;
            $this->invoice2 = 'INV-' . $tanggal . '-TRY1-' . $str;
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
        if (in_array($field, ['tambah', 'kurang', 'pakai'])) {
            $barang = Barang::find($this->barang_id);
            if ($barang) {
                $stok_awal = $barang->stok;
                $stok_baru = $stok_awal + $this->tambah - $this->kurang - $this->pakai;
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
            'rusak' => $this->pakai,
        ]);

        $katePakai = Kategori::where('name', 'like', '%Tray Terpakai%')->first();
        $kateTray = Kategori::where('name', 'like', '%Stok Tray%')->first();

        // TELUR PROK - Debit
        $prok = Transaksi::create([
            'invoice' => $this->invoice1,
            'name' => 'Tray Terpakai ' . $barang->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'type' => 'Debit',
            'total' => ($barang->hpp ?? 0) * ($this->pakai ?? 0),
        ]);

        DetailTransaksi::create([
            'transaksi_id' => $prok->id,
            'kategori_id' => $katePakai->id ?? null,
            'value' => $barang->hpp,
            'barang_id' => $barang->id,
            'kuantitas' => $this->pakai,
            'sub_total' => ($barang->hpp ?? 0) * ($this->pakai ?? 0),
        ]);

        // TELUR PROK - Kredit
        $tray = Transaksi::create([
            'invoice' => $this->invoice2,
            'name' => 'Tray Terpakai ' . $barang->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'type' => 'Kredit',
            'total' => ($barang->hpp ?? 0) * ($this->pakai ?? 0),
        ]);

        DetailTransaksi::create([
            'transaksi_id' => $tray->id,
            'kategori_id' => $kateTray->id ?? null,
            'value' => $barang->hpp,
            'barang_id' => $barang->id,
            'kuantitas' => $this->pakai,
            'sub_total' => ($barang->hpp ?? 0) * ($this->pakai ?? 0),
        ]);

        $this->success('Stok berhasil diperbarui!', redirectTo: '/stok-tray');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Create Transaksi Stok Tray" separator progress-indicator />

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
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end p-3 rounded-xl">
                        <x-input label="Tray Bertambah" wire:model.lazy="tambah" type="number" step="0.01" min="0" />
                        <x-input label="Tray Berkurang" wire:model.lazy="kurang" type="number" step="0.01" min="0" />
                        <x-input label="Tray Terpakai" wire:model.lazy="pakai" type="number" step="0.01" min="0" />
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <div class="flex flex-row sm:flex-row gap-2 justify-end">
                <x-button spinner label="Cancel" link="/stok-tray" />
                <x-button spinner label="Create" icon="o-paper-airplane" spinner="save" type="submit"
                    class="btn-primary" />
            </div>
        </x-slot:actions>
    </x-form>
</div>
