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

    // Semua barang
    public $barangs;

    // Barang yang difilter per detail
    public array $filteredBarangs = [];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'clients' => Client::all()->groupBy('type')->mapWithKeys(fn($group, $type) => [$type => $group->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()->toArray()])->toArray(),
            'kategoris' => Kategori::where('type', 'like', '%Liabilitas%')->orWhere('name', 'like', '%Hutang%')->get(),
            'optionType' => [['id' => 'Kredit', 'name' => 'Hutang Bertambah'], ['id' => 'Debit', 'name' => 'Hutang Berkurang']],
            'transaksi' => Transaksi::with('kategori')
                ->whereNull('linked_id') // ✅ Ambil hanya transaksi yang belum di-relasikan
                ->get()
                ->groupBy(fn($t) => $t->kategori->type) // ✅ Group by kategori.name
                ->mapWithKeys(
                    fn($group, $label) => [
                        $label => $group
                            ->map(
                                fn($t) => [
                                    'id' => $t->id,
                                    'name' => "{$t->invoice} | {$t->name} | Rp " . number_format($t->total),
                                ],
                            )
                            ->values()
                            ->toArray(),
                    ],
                )
                ->toArray(),
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

        $beban = Transaksi::create([
            'invoice' => $this->invoice,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'kategori_id' => $this->kategori_id,
            'client_id' => $this->client_id,
            'type' => $this->type,
            'total' => $this->total,
            'linked_id' => $this->linked_id ?? null,
        ]);

        $transaksi = Transaksi::find($this->linked_id);
        $transaksi->update([
            'linked_id' => $beban->id,
        ]);

        $this->success('Transaksi berhasil dibuat!', redirectTo: '/hutang');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Create Transaksi" separator progress-indicator />

    <x-form wire:submit="save">
        <div class="lg:grid grid-cols-5 gap-4">
            <div class="col-span-2">
                <x-header title="Basic Info" subtitle="Buat transaksi baru" size="text-2xl" />
            </div>
            <div class="col-span-3 grid gap-3">
                <div class="grid grid-cols-3 gap-4">
                    <x-input label="Invoice" wire:model="invoice" readonly />
                    <x-input label="User" :value="auth()->user()->name" readonly />
                    <x-datetime label="Date + Time" wire:model="tanggal" icon="o-calendar" type="datetime-local" />
                </div>
                <x-input label="Rincian" wire:model="name" />
                <div class="grid grid-cols-3 gap-4">
                    <x-select-group wire:model="client_id" label="Client" :options="$clients"
                        placeholder="Pilih Client" />
                    <x-select wire:model="kategori_id" label="Kategori" :options="$kategoris"
                        placeholder="Pilih Kategori" />
                    <x-select label="Tipe Transaksi" wire:model="type" :options="$optionType" placeholder="Pilih Tipe" />
                </div>
            </div>
        </div>

        <hr class="my-5" />

        <div class="lg:grid grid-cols-5 gap-4">
            <div class="col-span-2">
                <x-header title="Detail Items" subtitle="Tambah barang ke transaksi" size="text-2xl" />
            </div>
            <div class="col-span-3 grid gap-3">
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <x-select-group wire:model="linked_id" label="Relasi Transaksi" :options="$transaksi"
                            placeholder="Pilih Transaksi" />
                    </div>
                    <x-input label="Total" wire:model="total" prefix="Rp" money />
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/hutang" />
            <x-button spinner label="Create" icon="o-paper-airplane" spinner="save" type="submit"
                class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
