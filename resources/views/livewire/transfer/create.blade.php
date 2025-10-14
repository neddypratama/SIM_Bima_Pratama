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

    #[Rule('nullable')]
    public ?int $client_id = null;

    public ?int $kategori_id = null;

    #[Rule('required')]
    public ?string $type = null;

    #[Rule('required|integer')]
    public ?int $linked_id = null;

    public ?string $tanggal = null;

    public array $details = [];

    public $barangs = [];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'clients' => Client::all(),
            // 🔥 Group Transaksi untuk x-select-group
            'transaksi' => Transaksi::with(['client:id,name', 'details.kategori:id,name,type', 'linked.linkedTransaksi'])
                ->whereHas('details.kategori', function ($q) {
                    $q->where('name', 'not like', '%Kas%')->where('name', 'not like', '%Bank%');
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
            'kategori' => Kategori::where('name', 'like', 'Bank %')->get(),
            // 🔥 Opsi tipe transaksi dengan nama friendly
            'optionType' => [['id' => 'Debit', 'name' => 'Kas Masuk'], ['id' => 'Kredit', 'name' => 'Kas Keluar']],
        ];
    }

    public function mount(): void
    {
        $this->barangs = Barang::all();
        $this->user_id = auth()->id();
        $this->tanggal = now()->format('Y-m-d\TH:i');
        $this->updatedTanggal($this->tanggal);
    }

    public function updatedTanggal($value): void
    {
        if ($value) {
            $tanggal = \Carbon\Carbon::parse($value)->format('Ymd');
            $str = Str::upper(Str::random(4));
            $this->invoice = 'INV-' . $tanggal . '-TFR-' . $str;
        }
    }

    public function save(): void
    {
        $this->validate();

        $this->client_id = Transaksi::findOrFail($this->linked_id)->client_id;

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

        TransaksiLink::create([
            'transaksi_id' => $this->linked_id,
            'linked_id' => $tunai->id,
        ]);

        TransaksiLink::create([
            'transaksi_id' => $tunai->id,
            'linked_id' => $this->linked_id,
        ]);

        $this->success('Transaksi berhasil dibuat!', redirectTo: '/transfer');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Create Transaksi" separator progress-indicator />

    <x-form wire:submit="save">
        <!-- SECTION: Basic Info -->
        <x-card>
            <div class="lg:grid grid-cols-5 gap-4">
                <div class="col-span-2">
                    <x-header title="Basic Info" subtitle="Buat transaksi baru" size="text-2xl" />
                </div>
                <div class="col-span-3 grid gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <x-input label="Invoice" wire:model="invoice" readonly />
                        <x-input label="User" :value="auth()->user()->name" readonly />
                        <x-datetime label="Date + Time" wire:model="tanggal" icon="o-calendar" type="datetime-local" />
                    </div>
                    <x-input label="Rincian" wire:model="name" placeholder="Contoh: Bayar Bank BCA" />
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-select label="Kategori" wire:model="kategori_id" :options="$kategori"
                            placeholder="Pilih Kategori" />
                        <x-select label="Tipe Transaksi" wire:model="type" :options="$optionType" placeholder="Pilih Tipe" />
                    </div>
                </div>
            </div>
        </x-card>

        <!-- SECTION: Detail Items -->
        <x-card>
            <div class="lg:grid grid-cols-5 gap-4">
                <div class="col-span-2">
                    <x-header title="Detail Items" subtitle="Tambah detail transaksi" size="text-2xl" />
                </div>
                <div class="col-span-3 grid gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <x-choices-offline label="Pilih Transaksi" wire:model="linked_id" :options="$transaksi"
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
                                    {{ $transaksi->invoice . ' | ' . $transaksi->total . ' | ' . ($transaksi->client?->name ?? 'Tanpa Client') }}
                                @endscope
                            </x-choices-offline>
                        </div>
                        <x-input label="Total Pembayaran" wire:model="total" prefix="Rp" money />
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/transfer" />
            <x-button spinner label="Save" icon="o-paper-airplane" spinner="save" type="submit"
                class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
