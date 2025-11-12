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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use Toast, WithFileUploads;

    public ?Stok $stokModel = null;

    #[Rule('required')]
    public ?string $invoice = null;

    public ?int $barang_id = null;
    public ?string $tanggal = null;
    public ?int $user_id = null;
    public ?float $stokAsli = 0; // stok sebelum transaksi
    public ?float $stok = 0; // stok tampil di form
    public ?float $tambah = 0;
    public ?float $kurang = 0;
    public ?float $kotor = 0;
    public ?float $bentes = 0;
    public ?float $ceplok = 0;
    public ?float $prok = 0;

    public function mount($stok): void
    {
        $stokEdit = Stok::with('barang')->findOrFail($stok);
        $this->stokModel = $stokEdit;

        $this->invoice = $stokEdit->invoice ?? '';
        $this->barang_id = $stokEdit->barang_id;
        $this->tanggal = Carbon::parse($stokEdit->tanggal)->format('Y-m-d\TH:i');
        $this->user_id = $stokEdit->user_id;

        $barang = $stokEdit->barang;
        if ($barang) {
            // stok asli sebelum transaksi ini
            $this->stokAsli = $barang->stok - $stokEdit->tambah + ($stokEdit->kurang + $stokEdit->kotor + $stokEdit->bentes + $stokEdit->ceplok + $stokEdit->rusak);
            $this->stok = $this->stokAsli + $stokEdit->tambah - ($stokEdit->kurang + $stokEdit->kotor + $stokEdit->bentes + $stokEdit->ceplok + $stokEdit->rusak);
        } else {
            $this->stokAsli = $this->stok = 0;
        }

        $this->tambah = $stokEdit->tambah;
        $this->kurang = $stokEdit->kurang;
        $this->kotor = $stokEdit->kotor;
        $this->bentes = $stokEdit->bentes;
        $this->ceplok = $stokEdit->ceplok;
        $this->prok = $stokEdit->rusak;
    }

    public function with(): array
    {
        return [
            'users' => User::all(),
            'barangs' => Barang::whereHas('jenis', function ($q) {
                $q->where('name', 'like', '%Telur%');
            })->get(),
        ];
    }

    public function updatedBarangId($value): void
    {
        $barang = Barang::find($value);

        if ($barang) {
            if ($this->stokModel && $barang->id == $this->stokModel->barang_id) {
                // pakai transaksi lama
                $this->tambah = $this->stokModel->tambah;
                $this->kurang = $this->stokModel->kurang;
                $this->kotor = $this->stokModel->kotor;
                $this->bentes = $this->stokModel->bentes;
                $this->ceplok = $this->stokModel->ceplok;
                $this->prok = $this->stokModel->rusak;
                $this->stokAsli = $barang->stok - $this->tambah + ($this->kurang + $this->kotor + $this->bentes + $this->ceplok + $this->prok);
            } else {
                // reset input untuk barang baru
                $this->tambah = $this->kurang = $this->kotor = $this->bentes = $this->ceplok = $this->prok = 0;
                $this->stokAsli = $barang->stok;
            }

            $this->stok = $this->stokAsli + $this->tambah - ($this->kurang + $this->kotor + $this->bentes + $this->ceplok + $this->prok);
        } else {
            $this->stokAsli = $this->stok = 0;
            $this->tambah = $this->kurang = $this->kotor = $this->bentes = $this->ceplok = $this->prok = 0;
        }
    }

    public function updated($field): void
    {
        if (in_array($field, ['tambah', 'kurang', 'kotor', 'pecah'])) {
            $this->stok = $this->stokAsli + $this->tambah - ($this->kurang + $this->kotor + $this->bentes + $this->ceplok + $this->prok);
            $this->stok = max(0, $this->stok);
        }
    }

    public function update(): void
    {
        $barang = Barang::find($this->barang_id);
        if (!$barang) {
            $this->error('Barang tidak ditemukan.');
            return;
        }

        DB::transaction(function () use ($barang) {
            // === 1. Hitung stok akhir ===
            $stok_awal = $this->stokAsli ?? $barang->stok - $this->stokModel->tambah + ($this->stokModel->kurang + $this->stokModel->kotor + $this->stokModel->bentes + $this->stokModel->ceplok + $this->stokModel->rusak);

            $stok_akhir = $stok_awal + $this->tambah - ($this->kurang + $this->kotor + $this->bentes + $this->ceplok + $this->prok);
            $stok_akhir = max(0, $stok_akhir);

            // === 2. Update stok barang ===
            $barang->update(['stok' => $stok_akhir]);

            // === 3. Update stok model ===
            $this->stokModel->update([
                'barang_id' => $this->barang_id,
                'tanggal' => $this->tanggal,
                'tambah' => $this->tambah,
                'kurang' => $this->kurang,
                'kotor' => $this->kotor,
                'rusak' => $this->prok,
            ]);

            // === 4. Ambil suffix invoice stok ===
            $suffix = substr($this->stokModel->invoice, -4);
            $part = explode('-', $this->stokModel->invoice);
            $tanggal = $part[1];

            if ($this->kotor > 0) {
                // === 5. Update transaksi Telur Kotor ===
                $trxKotor = Transaksi::where('invoice', 'like', "INV-$tanggal-KTR-$suffix")->first();
                if ($trxKotor) {
                    $totalKotor = ($barang->hpp ?? 0) * ($this->kotor ?? 0);
                    $trxKotor->update([
                        'type' => 'Debit',
                        'total' => $totalKotor,
                        'tanggal' => $this->tanggal,
                    ]);

                    $detailKotor = DetailTransaksi::where('transaksi_id', $trxKotor->id)->first();
                    if ($detailKotor) {
                        $detailKotor->update([
                            'value' => $barang->hpp,
                            'kuantitas' => $this->kotor,
                            'sub_total' => $totalKotor,
                        ]);
                    }
                }

                // === 7. Update dua transaksi stok telur: TLR1 dan TLR2 ===
                $trxTelur1 = Transaksi::where('invoice', 'like', "INV-$tanggal-TLR1-$suffix")->first();
                if ($trxTelur1) {
                    $totalKotor = ($barang->hpp ?? 0) * ($this->kotor ?? 0);
                    $trxTelur1->update([
                        'type' => 'Kredit',
                        'total' => $totalKotor,
                        'tanggal' => $this->tanggal,
                    ]);

                    $detailKotor = DetailTransaksi::where('transaksi_id', $trxTelur1->id)->first();
                    if ($detailKotor) {
                        $detailKotor->update([
                            'value' => $barang->hpp,
                            'kuantitas' => $this->kotor,
                            'sub_total' => $totalKotor,
                        ]);
                    }
                }
            } else {
                // === 5. Update transaksi Telur Kotor ===
                $trxKotor = Transaksi::where('invoice', 'like', "INV-$tanggal-KTR-$suffix")->first();
                if ($trxKotor) {
                    $totalKotor = ($barang->hpp ?? 0) * ($this->kotor ?? 0);
                    $trxKotor->update([
                        'type' => 'Kredit',
                        'total' => $totalKotor * -1,
                        'tanggal' => $this->tanggal,
                    ]);

                    $detailKotor = DetailTransaksi::where('transaksi_id', $trxKotor->id)->first();
                    if ($detailKotor) {
                        $detailKotor->update([
                            'value' => $barang->hpp,
                            'kuantitas' => $this->kotor * -1,
                            'sub_total' => $totalKotor * -1,
                        ]);
                    }
                }

                // === 7. Update dua transaksi stok telur: TLR1 dan TLR2 ===
                $trxTelur1 = Transaksi::where('invoice', 'like', "INV-$tanggal-TLR1-$suffix")->first();
                if ($trxTelur1) {
                    $totalKotor = ($barang->hpp ?? 0) * ($this->kotor ?? 0);
                    $trxTelur1->update([
                        'type' => 'Debit',
                        'total' => $totalKotor * -1,
                        'tanggal' => $this->tanggal,
                    ]);

                    $detailKotor = DetailTransaksi::where('transaksi_id', $trxTelur1->id)->first();
                    if ($detailKotor) {
                        $detailKotor->update([
                            'value' => $barang->hpp,
                            'kuantitas' => $this->kotor * -1,
                            'sub_total' => $totalKotor * -1,
                        ]);
                    }
                }
            }

            // === 6. Update transaksi Telur Pecah ===
            $trxBentes = Transaksi::where('invoice', 'like', "INV-$tanggal-BTS-$suffix")->first();
            if ($trxBentes) {
                $totalPecah = ($barang->hpp ?? 0) * ($this->bentes ?? 0);
                $trxBentes->update([
                    'total' => $totalPecah,
                    'tanggal' => $this->tanggal,
                ]);

                $detailPecah = DetailTransaksi::where('transaksi_id', $trxBentes->id)->first();
                if ($detailPecah) {
                    $detailPecah->update([
                        'value' => $barang->hpp,
                        'kuantitas' => $this->bentes,
                        'sub_total' => $totalPecah,
                    ]);
                }
            }

            $trxTelur2 = Transaksi::where('invoice', 'like', "INV-$tanggal-TLR2-$suffix")->first();
            if ($trxTelur2) {
                $totalPecah = ($barang->hpp ?? 0) * ($this->bentes ?? 0);
                $trxTelur2->update([
                    'total' => $totalPecah,
                    'tanggal' => $this->tanggal,
                ]);

                $detailPecah = DetailTransaksi::where('transaksi_id', $trxTelur2->id)->first();
                if ($detailPecah) {
                    $detailPecah->update([
                        'value' => $barang->hpp,
                        'kuantitas' => $this->bentes,
                        'sub_total' => $totalPecah,
                    ]);
                }
            }

            // === 6. Update transaksi Telur Pecah ===
            $trxCeplok = Transaksi::where('invoice', 'like', "INV-$tanggal-CLK-$suffix")->first();
            if ($trxCeplok) {
                $totalPecah = ($barang->hpp ?? 0) * ($this->ceplok ?? 0);
                $trxCeplok->update([
                    'total' => $totalPecah,
                    'tanggal' => $this->tanggal,
                ]);

                $detailPecah = DetailTransaksi::where('transaksi_id', $trxCeplok->id)->first();
                if ($detailPecah) {
                    $detailPecah->update([
                        'value' => $barang->hpp,
                        'kuantitas' => $this->ceplok,
                        'sub_total' => $totalPecah,
                    ]);
                }
            }

            $trxTelur2 = Transaksi::where('invoice', 'like', "INV-$tanggal-TLR3-$suffix")->first();
            if ($trxTelur2) {
                $totalPecah = ($barang->hpp ?? 0) * ($this->ceplok ?? 0);
                $trxTelur2->update([
                    'total' => $totalPecah,
                    'tanggal' => $this->tanggal,
                ]);

                $detailPecah = DetailTransaksi::where('transaksi_id', $trxTelur2->id)->first();
                if ($detailPecah) {
                    $detailPecah->update([
                        'value' => $barang->hpp,
                        'kuantitas' => $this->ceplok,
                        'sub_total' => $totalPecah,
                    ]);
                }
            }

            // === 6. Update transaksi Telur Pecah ===
            $trxPecah = Transaksi::where('invoice', 'like', "INV-$tanggal-PRK-$suffix")->first();
            if ($trxPecah) {
                $totalPecah = ($barang->hpp ?? 0) * ($this->prok ?? 0);
                $trxPecah->update([
                    'total' => $totalPecah,
                    'tanggal' => $this->tanggal,
                ]);

                $detailPecah = DetailTransaksi::where('transaksi_id', $trxPecah->id)->first();
                if ($detailPecah) {
                    $detailPecah->update([
                        'value' => $barang->hpp,
                        'kuantitas' => $this->prok,
                        'sub_total' => $totalPecah,
                    ]);
                }
            }

            $trxTelur2 = Transaksi::where('invoice', 'like', "INV-$tanggal-TLR4-$suffix")->first();
            if ($trxTelur2) {
                $totalPecah = ($barang->hpp ?? 0) * ($this->prok ?? 0);
                $trxTelur2->update([
                    'total' => $totalPecah,
                    'tanggal' => $this->tanggal,
                ]);

                $detailPecah = DetailTransaksi::where('transaksi_id', $trxTelur2->id)->first();
                if ($detailPecah) {
                    $detailPecah->update([
                        'value' => $barang->hpp,
                        'kuantitas' => $this->prok,
                        'sub_total' => $totalPecah,
                    ]);
                }
            }
        });

        $this->success('Stok telur berhasil diperbarui!', redirectTo: '/stok-telur');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Edit Transaksi Stok Telur" separator progress-indicator />

    <x-form wire:submit="update">
        <x-card>
            <div class="lg:grid grid-cols-8 gap-4">
                <div class="col-span-2">
                    <x-header title="Basic Info" subtitle="Perbarui transaksi stok" size="text-2xl" />
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
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <div class="flex flex-row sm:flex-row gap-2 justify-end">
                <x-button spinner label="Cancel" link="/stok-telur" />
                <x-button spinner label="Update" icon="o-check-circle" spinner="update" type="submit"
                    class="btn-primary" />
            </div>
        </x-slot:actions>
    </x-form>
</div>
