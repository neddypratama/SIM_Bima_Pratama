<?php

use Livewire\Volt\Component;
use App\Models\Transaksi;
use App\Models\TransaksiLink;
use App\Models\DetailTransaksi;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Client;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;
use Illuminate\Support\Str;

new class extends Component {
    use Toast, WithFileUploads;

    #[Rule('required|unique:transaksis,invoice')]
    public string $invoice = '';

    #[Rule('required')]
    public string $name = '';

    #[Rule('required|integer|min:1')]
    public int $total = 0;

    #[Rule('required')]
    public ?int $user_id = null;

    #[Rule('required')]
    public ?int $kategori_id = null;

    #[Rule('nullable')]
    public ?int $client_id = null;

    #[Rule('required')]
    public ?string $type = null;

    #[Rule('nullable|integer')]
    public ?int $linked_id = null;

    public ?string $tanggal = null;

    public array $details = [];

    public $barangs;
    public array $filteredBarangs = [];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'clients' => Client::all(),
            'kategoris' => Kategori::where('type', 'like', '%Liabilitas%')->orWhere('name', 'like', '%Hutang%')->get(),
            'optionType' => [['id' => 'Kredit', 'name' => 'Hutang Bertambah'], ['id' => 'Debit', 'name' => 'Hutang Berkurang']],
            'transaksiOptions' => Transaksi::with(['client:id,name', 'details.kategori:id,name,type', 'linked.linkedTransaksi'])
                ->whereHas('details.kategori', function ($q) {
                    $q->where('type', 'not like', '%Kas%')->where('name', 'not like', '%Bank%')->where('type', 'like', '%Aset%');
                })
                ->get()
                ->filter(function ($t) {
                    // Hitung total transaksi yang sudah terhubung
                    $totalLinked = $t->linked->sum(fn($l) => $l->linkedTransaksi->total ?? 0);
                    $sisa = $t->total - $totalLinked;

                    // Hanya tampilkan jika masih ada sisa
                    return $sisa > 0;
                })
                ->values(),
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
            $tanggal = \Carbon\Carbon::parse($value)->format('Ymd');
            $str = Str::upper(Str::random(4));
            $this->invoice = 'INV-' . $tanggal . '-UTG-' . $str;
        }
    }

    public function save(): void
    {
        // ✅ Validasi seluruh input sekaligus
        $this->validate();

        $kategori = Kategori::find($this->kategori_id);
        $this->client_id = Transaksi::find($this->linked_id)->client_id ?? $this->client_id;
        if (in_array($kategori->name, ['Hutang Peternak', 'Hutang Karyawan', 'Hutang Pedagang'])) {
            if ($this->client_id) {
                $client = Client::findOrFail($this->client_id);
                if ($this->type == 'Kredit') {
                    // dd($this->client_id, $kategori->name, $this->type, $this->total);
                    $client->increment('bon', $this->total);
                } else {
                    $client->decrement('bon', $this->total);
                }
            }
        }

        $tunai = Transaksi::create([
            'invoice' => $this->invoice,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'client_id' => $this->client_id,
            'type' => $this->type,
            'total' => $this->total,
        ]);

        DetailTransaksi::create([
            'transaksi_id' => $tunai->id,
            'kategori_id' => $this->kategori_id,
            'kuantitas' => null,
            'value' => null,
            'sub_total' => $this->total,
        ]);

        if ($this->linked_id != null) {
            # code...
            TransaksiLink::create([
                'transaksi_id' => $this->linked_id,
                'linked_id' => $tunai->id,
            ]);

            TransaksiLink::create([
                'transaksi_id' => $tunai->id,
                'linked_id' => $this->linked_id,
            ]);
        }

        $this->success('Transaksi berhasil dibuat!', redirectTo: '/hutang');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Create Transaksi Hutang" separator progress-indicator />

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
                    <x-input label="Rincian" wire:model="name" placeholder="Contoh: Titipan Pak Agus" />
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <x-select label="Tipe Transaksi" wire:model.live="type" :options="$optionType"
                            placeholder="Pilih Tipe" />
                        <x-choices-offline placeholder="Pilih Client" wire:model.live="client_id" :options="$clients"
                            single searchable clearable label="Client">
                            {{-- Tampilan item di dropdown --}} @scope('item', $clients)
                                <x-list-item :item="$clients" sub-value="invoice">
                                    <x-slot:avatar>
                                        <x-icon name="fas.user" class="bg-primary/10 p-2 w-9 h-9 rounded-full" />
                                    </x-slot:avatar>
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
                                            <x-slot:avatar>
                                                <x-icon name="fas.receipt" class="bg-primary/10 p-2 w-9 h-9 rounded-full" />
                                            </x-slot:avatar>
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
                                        @php
                                            // Hitung total transaksi yang sudah terhubung
                                            $totalLinked = $transaksi->linked->sum(
                                                fn($l) => $l->linkedTransaksi->total ?? 0,
                                            );
                                            $sisa = $transaksi->total - $totalLinked;
                                        @endphp
                                        {{ $transaksi->invoice . ' | ' . 'Rp ' . number_format($sisa, 0, ',', '.') . ' | ' . ($transaksi->client?->name ?? 'Tanpa Client') }}
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
            <x-button spinner label="Save" icon="o-paper-airplane" spinner="save" type="submit"
                class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
