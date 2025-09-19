<?php

use Livewire\Volt\Component;
use App\Models\Transaksi;
use App\Models\Kategori;
use App\Models\Client;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;

new class extends Component {
    use Toast;

    public Transaksi $beban; // transaksi utama
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
    public ?int $client_id = null;

    public ?string $tanggal = null;

    public function with(): array
    {
        return [
            'users' => User::all(),
            'clients' => Client::all()->groupBy('type')->mapWithKeys(fn($group, $type) => [$type => $group->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()->toArray()])->toArray(),
            'kategoris' => Kategori::where('type', 'like', '%Aset%')->where('name', 'like', '%Bon%')->get(),
        ];
    }

    public function mount(Transaksi $transaksi): void
    {
        // Ambil transaksi utama
        $this->beban = Transaksi::with('kategori')->findOrFail($transaksi->id);

        // Set data form
        $this->invoice = $this->beban->invoice;
        $this->name = $this->beban->name;
        $this->total = $this->beban->total;
        $this->user_id = $this->beban->user_id;
        $this->kategori_id = $this->beban->kategori_id;
        $this->client_id = $this->beban->client_id;
        $this->tanggal = \Carbon\Carbon::parse($this->beban->tanggal)->format('Y-m-d\TH:i');
    }

    public function save(): void
    {
        $this->validate();

        // Update transaksi utama
        $this->beban->update([
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'kategori_id' => $this->kategori_id,
            'client_id' => $this->client_id,
            'total' => $this->total,
        ]);

        $this->success('Transaksi berhasil diperbarui!', redirectTo: '/piutang');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Edit Transaksi" separator progress-indicator />

    <x-form wire:submit="save">
        <div class="lg:grid grid-cols-5 gap-4">
            <div class="col-span-2">
                <x-header title="Basic Info" subtitle="Perbarui transaksi" size="text-2xl" />
            </div>
            <div class="col-span-3 grid gap-3">
                <div class="grid grid-cols-3 gap-4">
                    <x-input label="Invoice" wire:model="invoice" readonly />
                    <x-input label="User" :value="$users->firstWhere('id', $this->user_id)?->name" readonly />
                    <x-datetime label="Date + Time" wire:model="tanggal" icon="o-calendar" type="datetime-local" />
                </div>
                <x-input label="Rincian" wire:model="name" />
                <div class="grid grid-cols-2 gap-4">
                    <x-select-group wire:model="client_id" label="Client" :options="$clients"
                        placeholder="Pilih Client" />
                    <x-select wire:model="kategori_id" label="Kategori" :options="$kategoris"
                        placeholder="Pilih Kategori" />
                </div>
            </div>
        </div>

        <hr class="my-5" />

        <div class="lg:grid grid-cols-5 gap-4">
            <div class="col-span-2">
                <x-header title="Detail Items" subtitle="Perbarui nominal transaksi" size="text-2xl" />
            </div>
            <div class="col-span-3 grid gap-3">
                <x-input label="Total" wire:model="total" prefix="Rp" money />
            </div>
        </div>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/piutang" />
            <x-button spinner label="Update" icon="o-check" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
