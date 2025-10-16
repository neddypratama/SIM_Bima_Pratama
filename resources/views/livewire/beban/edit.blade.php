<?php

use Livewire\Volt\Component;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Kategori;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;

new class extends Component {
    use Toast;

    public Transaksi $beban; // transaksi utama
    public Transaksi $kas;   // transaksi linked

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
    public ?int $kategori_id = null;

    public ?string $tanggal = null;

    public array $details = [];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'kategoris' => Kategori::where('type', 'like', '%Pengeluaran%')->get(),
            'optionType' => [['id' => 'Debit', 'name' => 'Pengeluaran'], ['id' => 'Kredit', 'name' => 'Kembalian']],
        ];
    }

    public function mount(Transaksi $transaksi): void
    {
        // Ambil transaksi utama
        $this->beban = Transaksi::with('details.kategori')->findOrFail($transaksi->id);

        // Set data form
        $this->invoice = $this->beban->invoice;
        $this->invoice2 = $this->kas?->invoice ?? '';
        $this->name = $this->beban->name;
        $this->total = $this->beban->total;
        $this->user_id = $this->beban->user_id;
        $this->type = $this->beban->type;
        $this->tanggal = \Carbon\Carbon::parse($this->beban->tanggal)->format('Y-m-d\TH:i');

        foreach ($transaksi->details as $detail) {
            $this->details[] = [
                'kategori_id' => $detail->kategori_id,
                'sub_total' => $detail->sub_total,
            ];

            $this->kategori_id = $detail->kategori_id;
        }
    }

    public function save(): void
    {
        $this->validate();

        // Update transaksi utama
        $this->beban->update([
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'total' => $this->total,
        ]);

        $this->beban->details()->delete();
        DetailTransaksi::create([
            'transaksi_id' => $this->beban->id,
            'kategori_id' => $this->kategori_id,
            'kuantitas' => null,
            'value' => null,
            'sub_total' => $this->total,
        ]);

        $this->success('Transaksi berhasil diperbarui!', redirectTo: '/beban');
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
                        <div class="col-span-2">
                            <x-input label="Rincian" wire:model="name" placeholder="Contoh: Beban Transportasi" />
                        </div>
                        <x-choices-offline label="Kategori" wire:model="kategori_id" :options="$kategoris"
                            placeholder="Pilih Kategori" single clearable searchable />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-select label="Tipe Transaksi" wire:model="type" :options="$optionType" placeholder="Pilih Tipe" />
                        <x-input label="Total Pengeluaran" wire:model="total" prefix="Rp" money />
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/beban" />
            <x-button spinner label="Update" icon="o-check" spinner="save" type="submit"
                class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
