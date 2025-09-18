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

    public Transaksi $transaksi;

    public int $transaksi_id;

    #[Rule('required')]
    public string $name = '';

    #[Rule('required|integer|min:1')]
    public int $total = 0;

    #[Rule('required')]
    public ?int $user_id = null;

    #[Rule('required')]
    public ?int $client_id = null;

    #[Rule('required')]
    public ?int $kategori_id = null;

    public ?string $tanggal = null;

    #[Rule('required|array|min:1')]
    public array $details = [];

    public $barangs;
    public array $filteredBarangs = [];

    public string $invoice = '';
    public string $invoice2 = '';
    public string $invoice3 = '';

    public function with(): array
    {
        return [
            'users' => User::all(),
            'barangs' => $this->barangs,
            'stokKategori' => Kategori::where('name', 'like', '%Telur%')->where('type', 'like', '%Aset%')->get(),
            'pendapatanKategori' => Kategori::where('name', 'like', '%Telur%')->where('type', 'like', '%Pendapatan%')->get(),
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
        $this->transaksi = $transaksi;
        $this->transaksi_id = $transaksi->id;

        $this->invoice = $transaksi->invoice;
        $this->name = $transaksi->name;
        $this->user_id = $transaksi->user_id;
        $this->client_id = $transaksi->client_id;
        $this->kategori_id = $transaksi->kategori_id;
        $this->tanggal = Carbon::parse($transaksi->tanggal)->format('Y-m-d\TH:i:s');
        $this->total = $transaksi->total;

        // Load detail
        foreach ($transaksi->details as $index => $detail) {
            $this->details[$index] = [
                'barang_id' => $detail->barang_id,
                'value' => $detail->value,
                'kuantitas' => $detail->kuantitas ?? 1,
            ];
        }

        $this->barangs = Barang::all();

        // Filter barang per detail sesuai kategori
        $kategori = Kategori::find($this->kategori_id);
        foreach ($this->details as $index => $detail) {
            $this->filteredBarangs[$index] = Barang::whereHas('jenis', fn($q) => $q->where('kategori_id', $kategori->id))->get()->map(fn($barang) => ['id' => $barang->id, 'name' => $barang->name])->toArray();
        }
    }

    public function updatedDetails($value, $key): void
    {
        if (str_ends_with($key, '.value') || str_ends_with($key, '.kuantitas')) {
            $this->calculateTotal();
        }
    }

    private function calculateTotal(): void
    {
        $this->total = collect($this->details)->sum(fn($item) => ((int) ($item['value'] ?? 0)) * ((int) ($item['kuantitas'] ?? 1)));
    }

    public function save(): void
    {
        $this->validate([
            'details.*.value' => 'required|numeric|min:0',
            'details.*.barang_id' => 'required|exists:barangs,id',
            'details.*.kuantitas' => 'required|integer|min:1',
        ]);

        $transaksi = $this->transaksi;
        if ($transaksi->kategori->type == 'Pendapatan') {
            // Update transaksi
            $transaksi->update([
                'name' => $this->name,
                'user_id' => $this->user_id,
                'client_id' => $this->client_id,
                'kategori_id' => $this->kategori_id,
                'tanggal' => $this->tanggal,
                'total' => $this->total,
            ]);

            // Hapus detail lama & simpan baru
            DetailTransaksi::where('transaksi_id', $transaksi->id)->delete();
            foreach ($this->details as $item) {
                DetailTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'barang_id' => $item['barang_id'],
                    'kuantitas' => $item['kuantitas'] ?? 1,
                    'value' => $item['value'],
                ]);
            }
        } else {
            // Update transaksi utama
            $transaksi->update([
                'name' => $this->name,
                'user_id' => $this->user_id,
                'client_id' => $this->client_id,
                'kategori_id' => $this->kategori_id,
                'tanggal' => $this->tanggal,
                'total' => $this->total,
            ]);

            DetailTransaksi::where('transaksi_id', $transaksi->id)->delete();
            foreach ($this->details as $item) {
                DetailTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'barang_id' => $item['barang_id'],
                    'kuantitas' => $item['kuantitas'] ?? 1,
                    'value' => $item['value'],
                ]);
            }

            // Update transaksi HPP terkait (jika ada)
            $hpp = Transaksi::where('kategori_id', Kategori::where('name', 'HPP')->first()->id)
                ->where('invoice', $transaksi->invoice3 ?? null) // pastikan relasi benar
                ->first();

            if ($hpp) {
                $hpp->update([
                    'name' => $this->name,
                    'user_id' => $this->user_id,
                    'client_id' => $this->client_id,
                    'kategori_id' => $this->kategori_id,
                    'tanggal' => $this->tanggal,
                    'total' => $this->total,
                ]);

                DetailTransaksi::where('transaksi_id', $hpp->id)->delete();
                foreach ($this->details as $item) {
                    DetailTransaksi::create([
                        'transaksi_id' => $hpp->id,
                        'barang_id' => $item['barang_id'],
                        'kuantitas' => $item['kuantitas'] ?? 1,
                        'value' => $item['value'],
                    ]);
                }
            }
        }

        $this->success('Transaksi berhasil diperbarui!', redirectTo: '/telur-keluar');
    }

    public function addDetail(): void
    {
        $this->details[] = [
            'value' => 0,
            'barang_id' => null,
            'kuantitas' => 1,
        ];

        $index = count($this->details) - 1;
        if ($this->kategori_id) {
            $kategori = Kategori::find($this->kategori_id);
            $this->filteredBarangs[$index] = Barang::whereHas('jenis', fn($q) => $q->where('kategori_id', $kategori->id))->get()->map(fn($barang) => ['id' => $barang->id, 'name' => $barang->name])->toArray();
        } else {
            $this->filteredBarangs[$index] = [];
        }

        $this->calculateTotal();
    }

    public function removeDetail(int $index): void
    {
        unset($this->details[$index]);
        unset($this->filteredBarangs[$index]);
        $this->details = array_values($this->details);
        $this->filteredBarangs = array_values($this->filteredBarangs);
        $this->calculateTotal();
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
                <div class="grid grid-cols-2 gap-4">
                    <x-select-group wire:model="client_id" label="Client" :options="$clients"
                        placeholder="Pilih Client" />
                    @if ($transaksi->kategori->type == 'Pendapatan')
                        <x-select wire:model.live="kategori_id" label="Kategori" :options="$pendapatanKategori"
                            placeholder="Pilih Kategori" />
                    @else
                        <x-select wire:model.live="kategori_id" label="Kategori" :options="$stokKategori"
                            placeholder="Pilih Kategori" />
                    @endif
                </div>
            </div>
        </div>

        <hr class="my-5" />

        <div class="lg:grid grid-cols-5 gap-4">
            <div class="col-span-2">
                <x-header title="Detail Items" subtitle="Tambah barang ke transaksi" size="text-2xl" />
            </div>
            <div class="col-span-3 grid gap-3">
                @foreach ($details as $index => $item)
                    <div class="grid grid-cols-4 gap-2 items-center">
                        <x-select wire:model.live="details.{{ $index }}.barang_id" label="Barang"
                            :options="$filteredBarangs[$index] ?? []" placeholder="Pilih Barang" />
                        <x-input label="Value" wire:model.live="details.{{ $index }}.value" prefix="Rp "
                            money="IDR" />
                        <x-input label="Qty" wire:model.live="details.{{ $index }}.kuantitas" type="number"
                            min="1" />
                        <x-input label="Satuan" :value="$barangs->firstWhere('id', $item['barang_id'])?->satuan->name ?? '-'" readonly />
                    </div>
                    <x-button spinner icon="o-trash" class="bg-red-500 text-white"
                        wire:click="removeDetail({{ $index }})" />
                @endforeach

                <x-button spinner icon="o-plus" label="Tambah Item" wire:click="addDetail" class="mt-3" />
                <x-input label="Total" :value="number_format($total, 0, '.', ',')" prefix="Rp" readonly />
            </div>
        </div>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/telur-keluar" />
            <x-button spinner label="Create" icon="o-paper-airplane" spinner="save" type="submit"
                class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
