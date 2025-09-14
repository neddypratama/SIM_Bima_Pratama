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

    public Transaksi $transaksi;

    #[Rule('required')]
    public ?string $invoice = null;

    #[Rule('required')]
    public ?string $name = null;

    #[Rule('required|integer|min:1')]
    public ?int $total = null;

    #[Rule('required')]
    public ?int $user_id = null;

    #[Rule('required')]
    public ?int $client_id = null;

    public ?string $tanggal = null;

    #[Rule('required|array|min:1')]
    public array $details = [];

    public $bagianOptions = [
        ['id' => 'Pendapatan', 'name' => 'Pendapatan'],
        ['id' => 'Pengeluaran', 'name' => 'Pengeluaran']
    ];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'barangs' => Barang::all(),
            'kategoris' => Kategori::all(),
            'clients' => Client::where('type', 'like', '%Pedagang%')
                ->orWhere('type', 'like', '%Peternak%')
                ->get()
                ->groupBy('type')
                ->mapWithKeys(
                    fn($group, $type) => [
                        $type => $group
                            ->map(fn($c) => [
                                'id' => $c->id,
                                'name' => $c->name,
                            ])
                            ->values()
                            ->toArray(),
                    ],
                )
                ->toArray(),
        ];
    }

    public function mount(Transaksi $transaksi): void
    {
        $this->transaksi = $transaksi;
        $this->invoice = $transaksi->invoice;
        $this->name = $transaksi->name;
        $this->user_id = $transaksi->user_id;
        $this->client_id = $transaksi->client_id;
        $this->tanggal = \Carbon\Carbon::parse($this->transaksi->tanggal)->format('Y-m-d\TH:i:s');
        $this->total = $transaksi->total;
        $this->details = $transaksi->details()
            ->get()
            ->map(fn($d) => [
                'type' => $d->type,
                'value' => $d->value,
                'bagian' => $d->bagian,
                'barang_id' => $d->barang_id,
                'kuantitas' => $d->kuantitas,
                'kategori_id' => $d->kategori_id,
            ])
            ->toArray();

        $this->calculateTotal();
    }

    public function updatedTanggal($value): void
    {
        if ($value) {
            $tanggal = \Carbon\Carbon::parse($value)->format('Ymd');
            $this->invoice = 'INV-' . $tanggal . '-' . Str::upper(Str::random(10));
        }
    }

    public function updatedDetails($value, $key): void
    {
        if (str_ends_with($key, '.value') || str_ends_with($key, '.kuantitas')) {
            $this->calculateTotal();
        }

        if (str_ends_with($key, '.bagian')) {
            $parts = explode('.', $key);
            if (count($parts) === 2) {
                [$index, $field] = $parts;
                if (!empty($this->details[$index]['bagian'])) {
                    $this->details[$index]['type'] =
                        $this->details[$index]['bagian'] === 'Pendapatan' ? 'Kredit' : 'Debit';
                }
            }
        }
    }

    private function calculateTotal(): void
    {
        $this->total = collect($this->details)->sum(
            fn($item) => ((int) ($item['value'] ?? 0)) * ((int) ($item['kuantitas'] ?? 1))
        );
    }

    public function save(): void
    {
        $this->validate([
            'invoice' => 'required',
            'name' => 'required',
            'client_id' => 'required',
            'user_id' => 'required',
            'details' => 'required|array|min:1',
        ]);

        $this->transaksi->update([
            'invoice' => $this->invoice,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'total' => $this->total,
            'client_id' => $this->client_id,
        ]);

        // hapus detail lama lalu buat ulang
        $this->transaksi->details()->delete();

        foreach ($this->details as $item) {
            $this->validate([
                'details.*.type' => 'required|in:Kredit,Debit',
                'details.*.value' => 'required|integer|min:0',
                'details.*.bagian' => 'required|in:Pendapatan,Pengeluaran',
                'details.*.barang_id' => 'nullable|integer|exists:barangs,id',
                'details.*.kuantitas' => 'nullable|integer|min:1',
                'details.*.kategori_id' => 'nullable|integer|exists:kategoris,id',
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $this->transaksi->id,
                'type' => $item['type'],
                'value' => (int) $item['value'],
                'bagian' => $item['bagian'],
                'barang_id' => $item['barang_id'] ?? null,
                'kuantitas' => $item['kuantitas'] ?? null,
                'kategori_id' => $item['kategori_id'] ?? null,
            ]);
        }

        $this->success('Transaksi berhasil diperbarui!', redirectTo: '/transaksis');
    }

    public function addDetail(): void
    {
        $this->details[] = [
            'type' => 'Kredit',
            'value' => 0,
            'bagian' => null,
            'barang_id' => null,
            'kuantitas' => 1,
            'kategori_id' => null,
        ];
        $this->calculateTotal();
    }

    public function removeDetail(int $index): void
    {
        unset($this->details[$index]);
        $this->details = array_values($this->details);
        $this->calculateTotal();
    }
};

?>

<div class="p-4 space-y-6">
    <x-header title="Edit Transaksi" separator progress-indicator />

    <x-form wire:submit="save">
        <div class="lg:grid grid-cols-5">
            <div class="col-span-2">
                <x-header title="Basic Info" subtitle="Ubah transaksi" size="text-2xl" />
            </div>
            <div class="col-span-3 grid gap-3">
                <x-input label="Invoice" wire:model="invoice" readonly />
                <x-input label="Rincian" wire:model="name" />
                <x-input label='User' :value="auth()->user()->name" readonly />
                <x-select-group wire:model="client_id" label="Client" :options="$clients" placeholder="Pilih Client" />
                <x-datetime label="Date + Time" wire:model="tanggal" icon="o-calendar" type="datetime-local" />
            </div>
        </div>

        <hr class="my-5" />
        <div class="lg:grid grid-cols-5">
            <div class="col-span-2">
                <x-header title="Detail Items" subtitle="Ubah detail barang" size="text-2xl" />
            </div>
            <div class="col-span-3 grid gap-3">
                @foreach ($details as $index => $item)
                    <div class="grid grid-cols-3 gap-4 items-center">
                        <x-select wire:model.live="details.{{ $index }}.barang_id" label="Barang"
                            :options="$barangs" placeholder="Pilih Barang" />
                        <x-input label="Value" wire:model.live="details.{{ $index }}.value" prefix="Rp "
                            money="IDR" />
                        <x-input label="Qty" wire:model.live="details.{{ $index }}.kuantitas" type="number"
                            min="1" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <x-select wire:model.live="details.{{ $index }}.bagian" label="Bagian" :options="$bagianOptions"
                            placeholder="Pilih Bagian" />
                        <x-select wire:model.live="details.{{ $index }}.kategori_id" label="Kategori"
                            :options="$kategoris" placeholder="Pilih Kategori" />
                    </div>

                    <x-button spinner icon="o-trash" class="bg-red-500 text-white"
                        wire:click="removeDetail({{ $index }})" />
                @endforeach

                <x-button spinner icon="o-plus" label="Tambah Item" wire:click="addDetail" class="mt-3" />
                <x-input label="Total" :value="number_format($total, 0, '.', ',')" prefix="Rp" readonly />
            </div>
        </div>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/transaksis" />
            <x-button spinner label="Update" icon="o-paper-airplane" spinner="save" type="submit"
                class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
