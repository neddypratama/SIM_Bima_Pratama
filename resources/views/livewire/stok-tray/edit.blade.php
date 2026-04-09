<?php

use Livewire\Volt\Component;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Stok;
use App\Models\StokBatch;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use Toast, WithFileUploads;

    public ?Stok $stokModel = null;

    #[Rule('required')]
    public ?string $invoice = null;

    #[Rule('required')]
    public ?int $barang_id = null;

    public ?string $tanggal = null;
    public ?int $user_id = null;
    public float $stok = 0;
    public float $stokAsli = 0;

    #[Rule('nullable|numeric|min:0')]
    public float $tambah = 0;

    #[Rule('nullable|numeric|min:0')]
    public float $kurang = 0;

    #[Rule('nullable|numeric|min:0')]
    public float $pakai = 0;

    public function mount($stok): void
    {
        $stokEdit = Stok::with('barang')->findOrFail($stok);
        $this->stokModel = $stokEdit;

        $this->invoice = $stokEdit->invoice ?? '';
        $this->barang_id = $stokEdit->barang_id;
        $this->tanggal = Carbon::parse($stokEdit->tanggal)->format('Y-m-d\TH:i:s');
        $this->user_id = $stokEdit->user_id;

        $stokBatch = StokBatch::where('barang_id', $this->barang_id)->sum('qty_sisa');

        // stok asli sebelum transaksi ini
        $this->stokAsli = $stokBatch - $stokEdit->tambah + ($stokEdit->kurang + $stokEdit->rusak);
        $this->stok = $stokBatch;

        $this->tambah = $stokEdit->tambah;
        $this->kurang = $stokEdit->kurang;
        $this->pakai = $stokEdit->rusak;
    }

    public function with(): array
    {
        return [
            'users' => User::all(),
            'barangs' => Barang::whereHas('jenis', function ($q) {
                $q->where('name', 'like', '%Tray%');
            })->get(),
        ];
    }

    public function updatedBarangId($value): void
    {
        if (!$value) {
            $this->stok = $this->stokAsli = 0;
            return;
        }

        // total stok batch saat ini
        $stokBatch = StokBatch::where('barang_id', $value)->sum('qty_sisa') ?? 0;

        // jika edit & barang sama
        if ($this->stokModel && $value == $this->stokModel->barang_id) {
            // kembalikan ke kondisi sebelum transaksi ini
            $this->stokAsli = $stokBatch - $this->stokModel->tambah + ($this->stokModel->kurang + $this->stokModel->rusak);

            // pakai nilai transaksi lama
            $this->tambah = $this->stokModel->tambah;
            $this->kurang = $this->stokModel->kurang;
            $this->pakai = $this->stokModel->rusak;
        } else {
            // edit tapi ganti barang
            $this->stokAsli = $stokBatch;

            // reset input
            $this->tambah = 0;
            $this->kurang = 0;
            $this->pakai = 0;
        }

        // hitung stok akhir
        $this->stok = max(0, $this->stokAsli + $this->tambah - ($this->kurang + $this->pakai));
    }

    public function updated($field): void
    {
        if (in_array($field, ['tambah', 'kurang', 'pakai'])) {
            $this->stok = $this->stokAsli + $this->tambah - $this->kurang - $this->pakai;
            $this->stok = max(0, $this->stok);
        }
    }

    public function update(): void
    {
        $this->validate();

        if ($this->stok < 0) {
            $this->error('Stok tidak mencukupi');
            return;
        }

        if ($this->stokModel->status == 'Selesai') {
            DB::transaction(function () {
                $this->stokModel->update([
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                ]);

                $str = substr($this->stokModel->invoice, -4);
                $part = explode('-', $this->stokModel->invoice);
                $tanggal = $part[1];

                $tambah = Transaksi::where('invoice', 'like', "%$tanggal-TBH-$str")->first();
                $kurang = Transaksi::where('invoice', 'like', "%$tanggal-KRG-$str")->first();
                $pakai = Transaksi::where('invoice', 'like', "%$tanggal-PKI-$str")->first();
                $tray1 = Transaksi::where('invoice', 'like', "%$tanggal-TRY1-$str")->first();
                $tray2 = Transaksi::where('invoice', 'like', "%$tanggal-TRY2-$str")->first();
                $tray3 = Transaksi::where('invoice', 'like', "%$tanggal-TRY3-$str")->first();

                if ($tambah) {
                    $tambah->update([
                        'user_id' => $this->user_id,
                        'tanggal' => $this->tanggal,
                    ]);

                    $tray2->update([
                        'user_id' => $this->user_id,
                        'tanggal' => $this->tanggal,
                    ]);
                }

                if ($kurang) {
                    $kurang->update([
                        'user_id' => $this->user_id,
                        'tanggal' => $this->tanggal,
                    ]);

                    $tray3->update([
                        'user_id' => $this->user_id,
                        'tanggal' => $this->tanggal,
                    ]);
                }

                if ($pakai) {
                    $pakai->update([
                        'user_id' => $this->user_id,
                        'tanggal' => $this->tanggal,
                    ]);

                    $tray1->update([
                        'user_id' => $this->user_id,
                        'tanggal' => $this->tanggal,
                    ]);
                }
            });
        } elseif ($this->stokModel->status == 'Perbaikan') {
            DB::transaction(function () {
                $this->stokModel->update([
                    'user_id' => $this->user_id,
                    'barang_id' => $this->barang_id,
                    'tanggal' => $this->tanggal,
                    'tambah' => $this->tambah,
                    'kurang' => $this->kurang,
                    'rusak' => $this->pakai,
                ]);
            });
        }

        $this->success('Stok tray berhasil diperbarui!', redirectTo: '/stok-tray');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Edit Transaksi Stok Tray" separator progress-indicator />

    <x-form wire:submit="update">
        <x-card>
            <div class="lg:grid grid-cols-8 gap-4">
                <div class="col-span-2">
                    <x-header title="Basic Info" subtitle="Perbarui transaksi stok" size="text-2xl" />
                </div>
                <div class="col-span-6 grid gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input label="User" :value="auth()->user()->name" readonly />
                        <x-datetime label="Date + Time" wire:model="tanggal" icon="o-calendar" type="datetime-local"
                            step="1" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="col-span-2">
                            @if ($this->stokModel->status == 'Perbaikan')
                                <x-choices-offline placeholder="Pilih Barang" wire:model.live="barang_id"
                                    :options="$barangs" single searchable clearable label="Barang" />
                            @else
                                <x-choices-offline placeholder="Pilih Barang" wire:model.live="barang_id"
                                    :options="$barangs" single searchable clearable label="Barang" readonly />
                            @endif
                        </div>
                        <x-input label="Stok Awal" wire:model.live="stokAsli" type="number" step="0.01" readonly />
                        <x-input label="Stok Sekarang" wire:model.live="stok" type="number" step="0.01" readonly />
                    </div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="lg:grid grid-cols-8 gap-4">
                <div class="col-span-2">
                    <x-header title="Detail Items" subtitle="Perbarui detail stok" size="text-2xl" />
                </div>
                <div class="col-span-6 grid gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end p-3 rounded-xl">
                        @if ($this->stokModel->status == 'Perbaikan')
                            <x-input label="Obat Bertambah" wire:model.lazy="tambah" type="number" step="0.01"
                                min="0" />
                            <x-input label="Obat Berkurang" wire:model.lazy="kurang" type="number" step="0.01"
                                min="0" />
                            <x-input label="Obat Return" wire:model.lazy="kotor" type="number" step="0.01" />
                            <x-input label="Obat Kadaluarsa" wire:model.lazy="pecah" type="number" step="0.01"
                                min="0" />
                        @else
                            <x-input label="Obat Bertambah" wire:model.lazy="tambah" type="number" step="0.01"
                                min="0" readonly />
                            <x-input label="Obat Berkurang" wire:model.lazy="kurang" type="number" step="0.01"
                                min="0" readonly />
                            <x-input label="Obat Return" wire:model.lazy="kotor" type="number" step="0.01"
                                readonly />
                            <x-input label="Obat Kadaluarsa" wire:model.lazy="pecah" type="number" step="0.01"
                                min="0" readonly />
                        @endif
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <div class="flex flex-row sm:flex-row gap-2 justify-end">
                <x-button spinner label="Cancel" link="/stok-tray" />
                <x-button spinner label="Update" icon="o-check-circle" spinner="update" type="submit"
                    class="btn-primary" />
            </div>
        </x-slot:actions>
    </x-form>
</div>
