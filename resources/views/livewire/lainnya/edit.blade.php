<?php

use Livewire\Volt\Component;
use App\Models\Transaksi;
use App\Models\Kategori;
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

    public ?string $tanggal = null;

    public function with(): array
    {
        return [
            'users' => User::all(),
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
            'total' => $this->total,
        ]);

        $this->success('Transaksi berhasil diperbarui!', redirectTo: '/lainnya');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Update Transaksi {{ $this->invoice }}" separator progress-indicator />

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
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <x-input label="Rincian Transaksi" wire:model="name"
                                placeholder="Contoh: Penjualan tali tambang" />
                        </div>
                        <x-input label="Total Pembayaran" wire:model="total" prefix="Rp" money />
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/lainnya" />
            <x-button spinner label="Update" icon="o-check" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
