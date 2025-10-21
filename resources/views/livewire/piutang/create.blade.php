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
    public string $invoice1 = '';
    public string $invoice2 = '';

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

    public ?string $tanggal = null;

    #[Rule('required')]
    public ?int $bayar_id = null;

    #[Rule('required')]
    public ?string $type = null;

    public array $details = [];

    // Semua barang
    public $barangs;

    // Barang yang difilter per detail
    public array $filteredBarangs = [];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'clients' => Client::all(),
            'kategoris' => Kategori::where('type', 'like', '%Aset%')->where('name', 'like', '%Piutang%')->get(),
            'optionType' => [['id' => 'Debit', 'name' => 'Piutang Bertambah'], ['id' => 'Kredit', 'name' => 'Piutang Berkurang']],
            'kateBayar' => Kategori::where('name', 'like', '%Kas Tunai%')->orWhere('name', 'like', 'Bank%')->get(),
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
            $this->invoice = 'INV-' . $tanggal . '-BON-' . $str;
            $this->invoice1 = 'INV-' . $tanggal . '-TNI-' . $str;
            $this->invoice2 = 'INV-' . $tanggal . '-TFR-' . $str;
        }
    }

    public function save(): void
    {
        // ✅ Validasi seluruh input sekaligus
        $this->validate();

        $client = Client::find($this->client_id);
        if ($this->type == 'Debit') {
            $tipe = 'Kredit';
            // dd($this->client_id, $kategori->name, $this->type, $this->total);
            $client->increment('bon', $this->total);
        } else {
            $tipe = 'Debit';
            $client->decrement('bon', $this->total);
        }

        $piutang = Transaksi::create([
            'invoice' => $this->invoice,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'client_id' => $this->client_id,
            'type' => $this->type,
            'total' => $this->total,
        ]);

        DetailTransaksi::create([
            'transaksi_id' => $piutang->id,
            'kategori_id' => $this->kategori_id,
            'kuantitas' => null,
            'value' => null,
            'sub_total' => $this->total,
        ]);

        $bayar = Kategori::find($this->bayar_id);

        if ($bayar->name == 'Kas Tunai') {
            $tunai = Transaksi::create([
                'invoice' => $this->invoice1,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'client_id' => $this->client_id,
                'type' => $tipe,
                'total' => $this->total,
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $tunai->id,
                'kategori_id' => $this->bayar_id,
                'value' => null,
                'kuantitas' => null,
                'sub_total' => $this->total,
            ]);
        } else {
            $bank = Transaksi::create([
                'invoice' => $this->invoice2,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'client_id' => $this->client_id,
                'type' => $tipe,
                'total' => $this->total,
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $bank->id,
                'kategori_id' => $this->bayar_id,
                'value' => null,
                'kuantitas' => null,
                'sub_total' => $this->total,
            ]);
        }

        $this->success('Transaksi berhasil dibuat!', redirectTo: '/piutang');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Create Transaksi Piutang" separator progress-indicator />

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
                        <x-input label="Rincian" wire:model="name" placeholder="Contoh: Bon Pak Agus" />
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
                                {{ $clients->name . ' | ' . $clients->type . ' | ' . 'Rp ' . number_format($clients->bon, 0, ',', '.') }}
                            @endscope
                        </x-choices-offline>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-select label="Tipe Transaksi" wire:model.live="type" :options="$optionType"
                            placeholder="Pilih Tipe" />
                        <x-choices-offline label="Kategori" wire:model="kategori_id" :options="$kategoris"
                            placeholder="Pilih Kategori" single clearable searchable />
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
                    <div class="grid grid-cols-2 gap-4">
                        <x-choices-offline label="Metode Pembayaran" wire:model="bayar_id" :options="$kateBayar"
                            placeholder="Pilih Metode" single clearable searchable />
                        <x-input label="Total Pembayaran" wire:model="total" prefix="Rp" money />
                    </div>
                </div>
            </div>
        </x-card>

        <!-- ACTION BUTTONS -->
        <x-slot:actions>
            <x-button spinner label="Cancel" link="/piutang" />
            <x-button spinner label="Save" icon="o-paper-airplane" spinner="save" type="submit"
                class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
