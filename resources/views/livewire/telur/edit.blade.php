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
use Carbon\Carbon;

new class extends Component {
    use Toast, WithFileUploads;

    public int $transaksiId;

    #[Rule('required')]
    public string $invoice = '';

    #[Rule('required')]
    public string $name = '';

    #[Rule('required|integer|min:1')]
    public int $total = 0;

    #[Rule('required')]
    public ?int $user_id = null;

    #[Rule('required')]
    public ?int $client_id = null;

    public ?string $tanggal = null;

    #[Rule('required|array|min:1')]
    public array $details = [];

    public $barangs;
    public array $filteredBarangs = [];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'barangs' => $this->barangs,
            'kategoris' => Kategori::where('name', 'like', '%Telur%')
                ->where(function ($q) {
                    $q->where('type', 'like', '%Pendapatan%')->orWhere('type', 'like', '%Pengeluaran%');
                })
                ->get(),
            'clients' => Client::where('type', 'like', '%Pedagang%')
                ->orWhere('type', 'like', '%Peternak%')
                ->get()
                ->groupBy('type')
                ->mapWithKeys(
                    fn($group, $type) => [
                        $type => $group->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()->toArray(),
                    ],
                )
                ->toArray(),
        ];
    }

    public function mount(Transaksi $transaksi): void
    {
        $this->transaksiId = $transaksi->id;
        // Load semua barang
        $this->barangs = Barang::all();

        if ($transaksi) {
            $transaksi = Transaksi::with('details')->findOrFail($transaksi->id);
            $this->invoice = $transaksi->invoice;
            $this->name = $transaksi->name;
            $this->user_id = $transaksi->user_id;
            $this->client_id = $transaksi->client_id;
            $this->tanggal = Carbon::parse($transaksi->tanggal)->format('Y-m-d\TH:i:s');
            $this->total = $transaksi->total;

            $this->details = $transaksi->details
                ->map(function ($d) {
                    return [
                        'id' => $d->id,
                        'type' => $d->type,
                        'value' => $d->value,
                        'barang_id' => $d->barang_id,
                        'kuantitas' => $d->kuantitas,
                        'kategori_id' => $d->kategori_id,
                    ];
                })
                ->toArray();

            foreach ($this->details as $index => $detail) {
                if (!empty($detail['kategori_id'])) {
                    $kategori = Kategori::find($detail['kategori_id']);
                    $this->filteredBarangs[$index] = $kategori ? Barang::where('jenis_id', $kategori->jenis_id)->get() : collect();
                } else {
                    $this->filteredBarangs[$index] = collect();
                }
            }
        }
    }

    public function updatedDetails($value, $key)
    {
        if (str_ends_with($key, '.value') || str_ends_with($key, '.kuantitas')) {
            $this->calculateTotal();
        }

        if (str_ends_with($key, '.kategori_id')) {
            [$index, $field] = explode('.', $key);
            if (!empty($this->details[$index]['kategori_id'])) {
                $kategori = Kategori::find($this->details[$index]['kategori_id']);
                if ($kategori) {
                    $this->details[$index]['type'] = $kategori->type === 'Pendapatan' ? 'Kredit' : 'Debit';
                    $this->filteredBarangs[$index] = Barang::where('jenis_id', $kategori->jenis_id)->get();
                    $this->details[$index]['barang_id'] = null;
                }
            } else {
                $this->filteredBarangs[$index] = collect();
                $this->details[$index]['barang_id'] = null;
            }
        }
    }

    private function calculateTotal(): void
    {
        $this->total = collect($this->details)->sum(fn($item) => ((int) ($item['value'] ?? 0)) * ((int) ($item['kuantitas'] ?? 1)));
    }

    public function save(): void
    {
        $this->validate();

        $transaksi = Transaksi::findOrFail($this->transaksiId);
        $transaksi->update([
            'invoice' => $this->invoice,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'total' => $this->total,
            'client_id' => $this->client_id,
        ]);

        foreach ($this->details as $item) {
            $this->validate([
                'details.*.type' => 'required|in:Kredit,Debit',
                'details.*.value' => 'required|numeric|min:0',
                'details.*.barang_id' => 'required|exists:barangs,id',
                'details.*.kuantitas' => 'required|integer|min:1',
                'details.*.kategori_id' => 'required|exists:kategoris,id',
            ]);

            if (!empty($item['id'])) {
                $detail = DetailTransaksi::find($item['id']);
                $detail->update([
                    'type' => $item['type'],
                    'value' => $item['value'],
                    'barang_id' => $item['barang_id'],
                    'kuantitas' => $item['kuantitas'],
                    'kategori_id' => $item['kategori_id'],
                ]);
            } else {
                DetailTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'type' => $item['type'],
                    'value' => $item['value'],
                    'barang_id' => $item['barang_id'],
                    'kuantitas' => $item['kuantitas'],
                    'kategori_id' => $item['kategori_id'],
                ]);
            }
        }

        $this->success('Transaksi berhasil diupdate!', redirectTo: '/telur');
    }

    public function addDetail(): void
    {
        $this->details[] = [
            'type' => 'Kredit',
            'value' => 0,
            'barang_id' => null,
            'kuantitas' => 1,
            'kategori_id' => null,
        ];

        $index = count($this->details) - 1;
        $this->filteredBarangs[$index] = collect();
        $this->calculateTotal();
    }

    public function removeDetail(int $index): void
    {
        if (!empty($this->details[$index]['id'])) {
            DetailTransaksi::find($this->details[$index]['id'])?->delete();
        }
        unset($this->details[$index], $this->filteredBarangs[$index]);
        $this->details = array_values($this->details);
        $this->filteredBarangs = array_values($this->filteredBarangs);
        $this->calculateTotal();
    }
};
?>
<div class="p-4 space-y-6">
    <x-header title="Edit Transaksi" separator progress-indicator />

    <x-form wire:submit="save">
        <div class="lg:grid grid-cols-5 gap-4">
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

        <div class="lg:grid grid-cols-5 gap-4">
            <div class="col-span-2">
                <x-header title="Detail Items" subtitle="Ubah detail barang" size="text-2xl" />
            </div>
            <div class="col-span-3 grid gap-3">
                @foreach ($details as $index => $item)
                    <div class="grid grid-cols-2 gap-4 items-center">
                        <x-select wire:model.live="details.{{ $index }}.kategori_id" label="Kategori"
                            :options="$kategoris" placeholder="Pilih Kategori" />
                        <x-select wire:model.live="details.{{ $index }}.barang_id" label="Barang"
                            :options="$filteredBarangs[$index] ?? collect()" placeholder="Pilih Barang" />
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <x-input label="Value" wire:model.live="details.{{ $index }}.value" prefix="Rp "
                            money="IDR" />
                        <x-input label="Qty" wire:model.live="details.{{ $index }}.kuantitas" type="number"
                            min="1" />
                        <x-input label="Satuan" :value="$barangs->firstWhere('id', $item['barang_id'])->satuan->name ?? '-'" readonly />
                    </div>
                    <x-button spinner icon="o-trash" class="bg-red-500 text-white"
                        wire:click="removeDetail({{ $index }})" />
                @endforeach

                <x-button spinner icon="o-plus" label="Tambah Item" wire:click="addDetail" class="mt-3" />
                <x-input label="Total" :value="number_format($total, 0, '.', ',')" prefix="Rp" readonly />
            </div>
        </div>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/telur" />
            <x-button spinner label="Update" icon="o-paper-airplane" spinner="save" type="submit"
                class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
