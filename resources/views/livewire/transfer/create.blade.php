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

    public ?string $tanggal = null;

    public array $details = [];

    public $barangs = [];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'clients' => Client::all(),
            'kategori' => Kategori::where('name', 'like', 'Bank %')->get(),
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
            $this->invoice1 = 'INV-' . $tanggal . '-MDL-' . $str;
        }
    }

    public function save(): void
    {
        $this->validate();

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

        $kateModal = Kategori::where('name', 'like', '%Modal Awal')->first();

        if ($this->type == 'Debit') {
            $modal = Transaksi::create([
                'invoice' => $this->invoice1,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'client_id' => $this->client_id,
                'type' => 'Kredit',
                'total' => $this->total,
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $modal->id,
                'kategori_id' => $kateModal->id,
                'kuantitas' => null,
                'value' => null,
                'sub_total' => $this->total,
            ]);
        } else {
            $modal = Transaksi::create([
                'invoice' => $this->invoice1,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'client_id' => $this->client_id,
                'type' => 'Debit',
                'total' => $this->total,
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $modal->id,
                'kategori_id' => $kateModal->id,
                'kuantitas' => null,
                'value' => null,
                'sub_total' => $this->total,
            ]);
        }

        $this->success('Transaksi berhasil dibuat!', redirectTo: '/transfer');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Create Transaksi" separator progress-indicator />

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
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <x-input label="Rincian" wire:model="name" placeholder="Contoh: Bayar Bank BCA" />
                        </div>
                        <x-select label="Tipe Transaksi" wire:model="type" :options="$optionType" placeholder="Pilih Tipe" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-choices-offline label="Kategori" wire:model="kategori_id" :options="$kategori"
                            placeholder="Pilih Kategori" single clearable searchable />
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
