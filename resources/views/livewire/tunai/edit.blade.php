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
use Carbon\Carbon;

new class extends Component {
    use Toast, WithFileUploads;

    public Transaksi $transaksi;

    #[Rule('required')]
    public string $invoice = '';

    #[Rule('required')]
    public string $name = '';

    #[Rule('required|integer|min:0')]
    public int $total = 0;

    #[Rule('required')]
    public ?int $user_id = null;

    #[Rule('nullable')]
    public ?int $client_id = null;

    public ?int $kategori_id = null;

    #[Rule('required')]
    public ?string $type = null;

    #[Rule('nullable|integer')]
    public ?int $linked_id = null;

    public ?string $tanggal = null;

    public array $details = [];
    public $barangs = [];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'clients' => Client::all(),
            'optionType' => [['id' => 'Debit', 'name' => 'Kas Masuk'], ['id' => 'Kredit', 'name' => 'Kas Keluar']],
        ];
    }

    public function mount(Transaksi $transaksi): void
    {
        $this->transaksi = $transaksi;

        // Isi form dari data yang ada
        $this->invoice = $transaksi->invoice;
        $this->name = $transaksi->name;
        $this->user_id = $transaksi->user_id;
        $this->client_id = $transaksi->client_id;
        $this->type = $transaksi->type;
        $this->kategori_id = $transaksi->details->first()?->kategori_id ?? null;

        foreach ($transaksi->details as $detail) {
            $this->details[] = [
                'kategori_id' => $detail->kategori_id,
                'sub_total' => $detail->sub_total,
            ];
        }
        $this->total = $transaksi->total;
        $this->tanggal = Carbon::parse($transaksi->tanggal)->format('Y-m-d\TH:i:s');
    }

    public function save(): void
    {
        $this->validate();

        $tunai = $this->transaksi;

        // Update transaksi utama
        $tunai->update([
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

        $kateModal = Kategori::where('name', 'like', '%Modal Awal')->first();
        $suffix = substr($this->transaksi->invoice, -4);
        $modal = Transaksi::where('invoice', 'like', "%-MDL-$suffix")->first();

        if ($this->type == 'Debit') {
            $modal->update([
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'client_id' => $this->client_id,
                'type' => 'Kredit',
                'total' => $this->total,
            ]);
            $modal->details()->delete();
            DetailTransaksi::create([
                'transaksi_id' => $modal->id,
                'kategori_id' => $kateModal->id,
                'kuantitas' => null,
                'value' => null,
                'sub_total' => $this->total,
            ]);
        } else {
            $modal->update([
                'invoice' => $this->invoice,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'client_id' => $this->client_id,
                'type' => 'Debit',
                'total' => $this->total,
            ]);
            $modal->details()->delete();
            DetailTransaksi::create([
                'transaksi_id' => $modal->id,
                'kategori_id' => $kateModal->id,
                'kuantitas' => null,
                'value' => null,
                'sub_total' => $this->total,
            ]);
        }

        $this->success('Transaksi berhasil diperbarui!', redirectTo: '/tunai');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Update Transaksi {{ $this->invoice }}" separator progress-indicator />

    <x-form wire:submit="save">
        <!-- SECTION: Basic Info -->
        {{-- {{ dd($transaksi) }} --}}
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
                            placeholder="Contoh: Bayar Pembelian Telur Ayam Ras" />
                        <x-select label="Tipe Transaksi" wire:model="type" :options="$optionType" placeholder="Pilih Tipe" />
                    </div>
                    <x-input label="Nominal Pembayaran" wire:model="total" prefix="Rp" money />
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/tunai" />
            <x-button spinner label="Update" icon="o-check" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
