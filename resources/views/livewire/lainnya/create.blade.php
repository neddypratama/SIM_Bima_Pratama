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
    public ?int $user_id;

    #[Rule('nullable')]
    public ?int $client_id = null;

    #[Rule('required')]
    public ?int $kategori_id = null;

    #[Rule('required')]
    public ?int $bayar_id = null;

    #[Rule('required')]
    public ?string $tanggal = null;

    public $barangs;
    public $pokok;
    public $totalPokok;
    public float $harga_jual = 0;
    public array $filteredBarangs = [];
    public string $kas = '';

    public function with(): array
    {
        return [
            'pokok' => $this->pokok,
            'users' => User::all(),
            'kategoris' => Kategori::where('name', 'not like', '%Telur%')->where('name', 'not like', '%Pakan%')->where('name', 'not like', '%Obat-Obatan%')->where('name', 'not like', '%EggTray%')->where('name', 'not like', '%Truk%')->where('type', 'Pendapatan')->get(),
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
            $this->invoice = 'INV-' . $tanggal . '-LNY-' . $str;
            $this->invoice1 = 'INV-' . $tanggal . '-TNI-' . $str;
            $this->invoice2 = 'INV-' . $tanggal . '-TFR-' . $str;
        }
    }

    public function save(): void
    {
        $this->validate();

        $transaksi = Transaksi::create([
            'invoice' => $this->invoice,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'type' => 'Kredit',
            'total' => $this->total,
        ]);

        DetailTransaksi::create([
            'transaksi_id' => $transaksi->id,
            'kategori_id' => $this->kategori_id,
            'value' => null,
            'kuantitas' => null,
            'sub_total' => $this->total,
        ]);

        $bayar = Kategori::find($this->bayar_id);

        if ($bayar->name == 'Kas Tunai') {
            $tunai = Transaksi::create([
                'invoice' => $this->invoice1,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'type' => 'Debit',
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
                'type' => 'Debit',
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

        $this->success('Transaksi berhasil dibuat!', redirectTo: '/lainnya');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Create Transaksi Pendapatan Lainnya" separator progress-indicator />

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
                        <x-input label="Rincian Transaksi" wire:model="name"
                            placeholder="Contoh: Penjualan tali tambang" />
                        <x-choices-offline label="Metode Pembayaran" wire:model="bayar_id" :options="$kateBayar"
                            placeholder="Pilih Metode" single clearable searchable />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-choices-offline label="Kategori" wire:model="kategori_id" :options="$kategoris"
                            placeholder="Pilih Kategori" single clearable searchable />
                        <x-input label="Total Pembayaran" wire:model="total" prefix="Rp" money />
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/lainnya" />
            <x-button spinner label="Save" icon="o-paper-airplane" spinner="save" type="submit"
                class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
