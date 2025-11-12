<?php

use Livewire\Volt\Component;
use App\Models\Truk;
use App\Models\DetailTruk;
use App\Models\Kategori;
use App\Models\Client;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;

new class extends Component {
    use Toast;

    public Truk $beban; // Truk utama
    public ?Truk $bayar; // Truk linked

    #[Rule('required')]
    public string $invoice = '';

    #[Rule('required')]
    public string $name = '';

    #[Rule('required|integer|min:1')]
    public int $total = 0;

    #[Rule('required')]
    public ?int $user_id = null;

    #[Rule('required')]
    public ?string $type = null;

    #[Rule('required')]
    public ?int $client_id = null;

    public ?int $kategori_id = null;

    public ?string $tanggal = null;

    public array $details = [];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'clients' => Client::where('type', 'like', '%Truk%')->get(),
            'optionType' => [['id' => 'Debit', 'name' => 'Pengeluaran'], ['id' => 'Kredit', 'name' => 'Pemasukkan']],
            'kateBayar' => Kategori::where('name', 'like', '%Kas Tunai%')->orWhere('name', 'like', 'Bank%')->get(),
        ];
    }

    public function mount(Truk $truk): void
    {
        // Ambil Truk utama
        $this->beban = Truk::findOrFail($truk->id);

        // Set data form
        $this->invoice = $this->beban->invoice;
        $this->name = $this->beban->name;
        $this->total = $this->beban->total;
        $this->user_id = $this->beban->user_id;
        $this->client_id = $this->beban->client_id;
        $this->type = $this->beban->type;
        $this->tanggal = \Carbon\Carbon::parse($this->beban->tanggal)->format('Y-m-d\TH:i');
    }

    public function save(): void
    {
        $this->validate();

        if ($this->type == 'Debit') {
            $this->beban->update([
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'client_id' => $this->client_id,
                'type' => 'Debit',
                'total' => $this->total,
            ]);
        } else {
            $this->beban->update([
                'name' => $this->name,
                'user_id' => $this->user_id,
                'client_id' => $this->client_id,
                'tanggal' => $this->tanggal,
                'type' => 'Kredit',
                'total' => $this->total,
            ]);
        }

        $this->success('Truk berhasil diperbarui!', redirectTo: '/transport');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Update Truk {{ $this->invoice }}" separator progress-indicator />

    <x-form wire:submit="save">
        <!-- SECTION: Basic Info -->
        <x-card>
            <div class="lg:grid grid-cols-8 gap-4">
                <div class="col-span-2">
                    <x-header title="Basic Info" subtitle="Buat Truk baru" size="text-2xl" />
                </div>
                <div class="col-span-6 grid gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <x-input label="Invoice" wire:model="invoice" readonly />
                        <x-input label="User" :value="auth()->user()->name" readonly />
                        <x-datetime label="Date + Time" wire:model="tanggal" icon="o-calendar" type="datetime-local" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input label="Rincian" wire:model="name" placeholder="Contoh: Beban Transportasi" />
                        <x-choices-offline label="Client" wire:model="client_id" :options="$clients"
                            placeholder="Pilih Client" single clearable searchable />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-select label="Tipe Truk" wire:model="type" :options="$optionType" placeholder="Pilih Tipe" />
                        <x-input label="Total Pengeluaran" wire:model="total" prefix="Rp" money />
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/transport" />
            <x-button spinner label="Update" icon="o-check" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
