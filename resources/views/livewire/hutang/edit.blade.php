<?php

use Livewire\Volt\Component;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\TransaksiLink;
use App\Models\Kategori;
use App\Models\Client;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;

new class extends Component {
    use Toast;

    public Transaksi $hutang; // transaksi utama
    public Transaksi $kas; // transaksi linked

    #[Rule('required')]
    public string $invoice = '';

    #[Rule('required')]
    public string $name = '';

    #[Rule('required|integer|min:1')]
    public int $total = 0;

    #[Rule('required')]
    public ?int $user_id = null;

    #[Rule('required')]
    public ?int $kategori_id = null;

    #[Rule('required')]
    public ?string $type = null;

    #[Rule('nullable')]
    public ?int $client_id = null;

    #[Rule('nullable|integer')]
    public ?int $linked_id = null;

    public ?string $tanggal = null;

    public array $details = [];

    public function mount(Transaksi $transaksi): void
    {
        // Ambil transaksi utama
        $this->hutang = $transaksi;

        // Set data form
        $this->invoice = $this->hutang->invoice;
        $this->name = $this->hutang->name;
        $this->total = $this->hutang->total;
        $this->user_id = $this->hutang->user_id;
        $this->kategori_id = $this->hutang->kategori_id;
        $this->client_id = $this->hutang->client_id;
        $this->type = $this->hutang->type;
        foreach ($transaksi->details as $detail) {
            $this->details[] = [
                'kategori_id' => $detail->kategori_id,
                'sub_total' => $detail->sub_total,
            ];

            $this->kategori_id = $detail->kategori_id;
        }

        $this->linked_id = $transaksi->linked->first()?->linked_id ?? null;
        $this->tanggal = \Carbon\Carbon::parse($this->hutang->tanggal)->format('Y-m-d\TH:i');
    }

    public function with(): array
    {
        return [
            'users' => User::all(),
            'clients' => Client::all(),
            'kategoris' => Kategori::where('type', 'like', '%Liabilitas%')->where('name', 'like', '%Hutang%')->get(),
            'optionType' => [['id' => 'Kredit', 'name' => 'Hutang Bertambah'], ['id' => 'Debit', 'name' => 'Hutang Berkurang']],
            'transaksiOptions' => Transaksi::with(['client:id,name', 'details.kategori:id,name,type', 'linked.linkedTransaksi'])
                ->whereHas('details.kategori', function ($q) {
                    $q->where('type', 'not like', '%Kas%')->where('name', 'not like', '%Bank%')->where('type', 'like', '%Aset%');
                })
                ->get()
                ->filter(function ($t) {
                    $totalLinked = $t->linked->sum(fn($l) => $l->linkedTransaksi->total ?? 0);
                    $sisa = $t->total - $totalLinked;

                    // akses this->transaksi dari closure dengan use()
                    return $sisa > 0 || $t->id === $this->hutang->linked->first()?->linked_id;
                })
                ->values(),
        ];
    }

    public function save(): void
    {
        $this->validate();

        $tunai = $this->hutang;

        $this->client_id = Transaksi::find($this->linked_id)->client_id ?? $this->client_id;

        $kategoriHutang = ['Hutang Peternak', 'Hutang Karyawan', 'Hutang Pedagang'];
        $oldKategori = $this->hutang->details->first()->kategori->name ?? null;
        $oldClientId = $this->hutang->client_id;
        $oldType = $this->hutang->type;
        $oldTotal = $this->hutang->total;
        $kategoriBaru = Kategori::find($this->kategori_id)->name ?? null;

        if (in_array($oldKategori, $kategoriHutang) && $oldClientId) {
            $clientLama = Client::find($oldClientId);
            if ($clientLama) {
                if ($oldType == 'Kredit') {
                    $clientLama->decrement('bon', $oldTotal);
                } else {
                    $clientLama->increment('bon', $oldTotal);
                }
            }
        }

        // Jika sekarang termasuk kategori hutang → terapkan perubahan baru
        if (in_array($kategoriBaru, $kategoriHutang) && $this->client_id) {
            $clientBaru = Client::findOrFail($this->client_id);
            if ($this->type == 'Kredit') {
                $clientBaru->increment('bon', $this->total);
            } else {
                $clientBaru->decrement('bon', $this->total);
            }
        }

        // Update transaksi utama
        $tunai->update([
            'invoice' => $this->invoice,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'client_id' => $this->client_id,
            'type' => $this->type,
            'total' => $this->total,
        ]);

        $tunai->details()->delete();
        DetailTransaksi::create([
            'transaksi_id' => $tunai->id,
            'kategori_id' => $this->kategori_id,
            'kuantitas' => null,
            'value' => null,
            'sub_total' => $this->total,
        ]);

        $link = TransaksiLink::where('linked_id', $tunai->id)->first();
        if ($link) {
            $link->delete();
        }

        // Hapus link lama
        $tunai->linked()->delete();

        // Buat link baru jika ada
        if ($this->linked_id) {
            TransaksiLink::create([
                'transaksi_id' => $tunai->id,
                'linked_id' => $this->linked_id,
            ]);

            TransaksiLink::create([
                'transaksi_id' => $this->linked_id,
                'linked_id' => $tunai->id,
            ]);
        }

        $this->success('Transaksi berhasil diperbarui!', redirectTo: '/hutang');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Update Transaksi {{ $this->invoice }}" separator progress-indicator />

    <x-form wire:submit="save">
        <!-- SECTION: Basic Info -->
        <x-card>
            <div class="lg:grid grid-cols-8 gap-4">
                <div class="col-span-2">
                    <x-header title="Basic Info" subtitle="Buat transaksi baru" size="text-2xl" />
                </div>

                <div class="col-span-6 grid gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <x-input label="Invoice" wire:model="invoice" readonly />
                        <x-input label="User" :value="auth()->user()->name" readonly />
                        <x-datetime label="Date + Time" wire:model="tanggal" icon="o-calendar" type="datetime-local" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input label="Rincian" wire:model="name" placeholder="Contoh: Titipan Pak Agus" />
                        <x-select label="Tipe Transaksi" wire:model.live="type" :options="$optionType"
                            placeholder="Pilih Tipe" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-choices-offline placeholder="Pilih Client" wire:model.live="client_id" :options="$clients"
                            single searchable clearable label="Client">
                            {{-- Tampilan item di dropdown --}} @scope('item', $clients)
                                <x-list-item :item="$clients" sub-value="invoice">
                                    <x-slot:actions>
                                        <x-badge :value="$clients->type ?? 'Tanpa Client'" class="badge-soft badge-secondary badge-sm" />

                                    </x-slot:actions>
                                </x-list-item>
                            @endscope

                            {{-- Tampilan ketika sudah dipilih --}}
                            @scope('selection', $clients)
                                {{ $clients->name . ' | ' . $clients->type }}
                            @endscope
                        </x-choices-offline>
                        <x-select wire:model="kategori_id" label="Kategori" :options="$kategoris"
                            placeholder="Pilih Kategori" />
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
                    @if ($type == 'Kredit')
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <x-choices-offline label="Pilih Transaksi" wire:model="linked_id" :options="$transaksiOptions"
                                    placeholder="Cari atau pilih transaksi" searchable clearable single>
                                    {{-- Tampilan item di dropdown --}}
                                    @scope('item', $transaksi)
                                        <x-list-item :item="$transaksi" sub-value="invoice">
                                            <x-slot:actions>
                                                @php
                                                    // Hitung total transaksi yang sudah terhubung
                                                    $totalLinked = $transaksi->linked->sum(
                                                        fn($l) => $l->linkedTransaksi->total ?? 0,
                                                    );
                                                    $sisa = $transaksi->total - $totalLinked;
                                                @endphp

                                                <x-badge :value="'Rp ' . number_format($sisa, 0, ',', '.')" class="badge-soft badge-primary badge-sm" />
                                                <x-badge :value="$transaksi->client?->name ?? 'Tanpa Client'" class="badge-soft badge-secondary badge-sm" />

                                            </x-slot:actions>
                                        </x-list-item>
                                    @endscope

                                    {{-- Tampilan ketika sudah dipilih --}}
                                    @scope('selection', $transaksi)
                                        {{ $transaksi->invoice . ' | ' . 'Rp ' . number_format($transaksi->total, 0, ',', '.') . ' | ' . ($transaksi->client?->name ?? 'Tanpa Client') }}
                                    @endscope
                                </x-choices-offline>
                            </div>
                            <x-input label="Total Pembayaran" wire:model="total" prefix="Rp" money />
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-4">
                            <x-input label="Total Pembayaran" wire:model="total" prefix="Rp" money />
                        </div>
                    @endif
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/hutang" />
            <x-button spinner label="Update" icon="o-check" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
