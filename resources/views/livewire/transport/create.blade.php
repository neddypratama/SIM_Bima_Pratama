<?php

use Livewire\Volt\Component;
use App\Models\Transaksi;
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
    public ?int $client_id = null;

    #[Rule('required')]
    public ?string $type = null;

    public ?string $tanggal = null;

    public array $details = [];

    // Semua barang
    public $barangs;

    // Barang yang difilter per detail
    public array $filteredBarangs = [];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'clients' => Client::where('type', 'Truk')->get(),
            'optionType' => [['id' => 'Debit', 'name' => 'Pengeluaran'], ['id' => 'Kredit', 'name' => 'Pemasukan']],
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
            $this->invoice = 'INV-' . $tanggal . '-PGN-' . $str;
            $this->invoice1 = 'INV-' . $tanggal . '-PDT-' . $str;
            $this->invoice2 = 'INV-' . $tanggal . '-TNI-' . $str;
            $this->invoice3 = 'INV-' . $tanggal . '-TFR-' . $str;
        }
    }

    public function save(): void
    {
        // ✅ Validasi seluruh input sekaligus
        $this->validate();

        $katePemasukan = Kategori::where('name', 'Pendapatan Truk')->first()->id;
        $katePengeluaran = Kategori::where('name', 'Pengeluaran Truk')->first()->id;

        if ($this->type == 'Debit') {
            $beban = Transaksi::create([
                'invoice' => $this->invoice,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'client_id' => $this->client_id,
                'type' => 'Debit',
                'total' => $this->total,
            ]);
            DetailTransaksi::create([
                'transaksi_id' => $beban->id,
                'kategori_id' => $katePengeluaran,
                'sub_total' => $this->total,
            ]);
        } else {
            $beban = Transaksi::create([
                'invoice' => $this->invoice1,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'client_id' => $this->client_id,
                'type' => 'Kredit',
                'total' => $this->total,
            ]);
            DetailTransaksi::create([
                'transaksi_id' => $beban->id,
                'kategori_id' => $katePemasukan,
                'sub_total' => $this->total,
            ]);
        }

        $this->success('Transaksi berhasil dibuat!', redirectTo: '/transport');
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input label="Rincian" wire:model="name" placeholder="Contoh: Setoran Transportasi" />
                        <x-choices-offline label="Client" wire:model="client_id" :options="$clients"
                            placeholder="Pilih Client" single clearable searchable />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-select label="Tipe Transaksi" wire:model="type" :options="$optionType" placeholder="Pilih Tipe" />
                        <x-input label="Total Pengeluaran" wire:model="total" prefix="Rp" money />
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/transport" />
            <x-button spinner label="Save" icon="o-paper-airplane" spinner="save" type="submit"
                class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
