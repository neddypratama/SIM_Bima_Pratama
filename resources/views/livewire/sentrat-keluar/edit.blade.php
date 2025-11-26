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
use Illuminate\Support\Facades\DB;

new class extends Component {
    use Toast, WithFileUploads;

    public Transaksi $transaksi;

    #[Rule('required')]
    public string $invoice = '';
    public string $invoice2 = '';
    public string $invoice3 = '';

    #[Rule('required')]
    public string $name = '';

    #[Rule('required|numeric|min:1')]
    public float $total = 0;

    #[Rule('required')]
    public ?int $user_id = null;

    #[Rule('required')]
    public ?int $client_id = null;

    #[Rule('required')]
    public ?string $tanggal = null;

    #[Rule('required|array|min:1')]
    public array $details = [];
    
    public $barangs;
    public $pokok;
    public array $filteredBarangs = [];
    public ?int $totalPokok = 0;

    public function mount(Transaksi $transaksi): void
    {
        $this->transaksi = $transaksi->load('details');
        $this->invoice = $transaksi->invoice;
        $this->name = $transaksi->name;
        $this->user_id = $transaksi->user_id;
        $this->client_id = $transaksi->client_id;
        $this->tanggal = \Carbon\Carbon::parse($transaksi->tanggal)->format('Y-m-d\TH:i:s');
        $this->total = $transaksi->total;

        $this->barangs = Barang::all();
        $this->pokok = Barang::all();

        // isi details
        foreach ($transaksi->details as $detail) {
            $this->details[] = [
                'kategori_id' => $detail->kategori_id,
                'barang_id' => $detail->barang_id,
                'value' => $detail->value,
                'kuantitas' => $detail->kuantitas,
                'max_qty' => Barang::find($detail->barang_id)->stok + $detail->kuantitas,
                'hpp' => Barang::find($detail->barang_id)->hpp ?? 0,
            ];
        }

        $kategori = Kategori::where('name', 'Stok Pakan')->first();
        foreach ($this->details as $index => $detail) {
            $this->filteredBarangs[$index] = Barang::whereHas('jenis', fn($q) => $q->where('kategori_id', $kategori->id))->get()->map(fn($barang) => ['id' => $barang->id, 'name' => $barang->name])->toArray();
        }
    }

    public function with(): array
    {
        return [
            'pokok' => $this->pokok,
            'users' => User::all(),
            'barangs' => $this->barangs,
            'kategoris' => Kategori::where('name', 'like', '%Pakan%')->where('type', 'like', '%Pendapatan%')->get(),
            'clients' => Client::where('type', 'like', '%Pedagang%')->orWhere('type', 'like', '%Peternak%')->get(),
        ];
    }

    private function calculateTotal(): void
    {
        $this->total = collect($this->details)->sum(fn($item) => ($item['value'] ?? 0) * ($item['kuantitas'] ?? 1));

        $this->totalPokok = collect($this->details)->sum(function ($item) {
            if (!$item['barang_id']) {
                return 0;
            }
            $barang = Barang::find($item['barang_id']);
            $hpp = isset($item['hpp']) && $item['hpp'] > 0 ? (float) $item['hpp'] : (float) ($barang->hpp ?? 0);
            $qty = $item['kuantitas'] ?? 0;
            return $hpp * $qty;
        });
    }

    public function save(): void
    {
        $this->validate([
            'details' => 'required|array|min:1',
            'details.*.barang_id' => 'required|exists:barangs,id',
            'details.*.value' => 'required|numeric|min:0',
            'details.*.kuantitas' => 'required|numeric|min:1',
            'details.*.kategori_id' => 'required|exists:kategoris,id',
        ]);

        foreach ($this->details as $i => $item) {
            if ($item['max_qty'] !== null && $item['kuantitas'] > $item['max_qty']) {
                $this->addError("details.$i.kuantitas", 'Qty tidak boleh melebihi stok barang.');
                return;
            }
        }

        // === 3. Update Transaksi Pendapatan (Kredit Utama) ===
        $this->transaksi->update([
            'name' => $this->name,
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'tanggal' => $this->tanggal,
            'total' => $this->total,
            'type' => 'Kredit',
        ]);

        // Replace detail pendapatan
        $this->transaksi->details()->delete();
        foreach ($this->details as $item) {
            DetailTransaksi::create([
                'transaksi_id' => $this->transaksi->id,
                'kategori_id' => $item['kategori_id'],
                'barang_id' => $item['barang_id'],
                'value' => $item['value'],
                'kuantitas' => $item['kuantitas'],
                'sub_total' => ($item['value'] ?? 0) * ($item['kuantitas'] ?? 1),
            ]);
        }
        $this->success('Transaksi berhasil diupdate!', redirectTo: '/sentrat-keluar');
    }

    public function addDetail(): void
    {
        $this->details[] = [
            'value' => 0,
            'kategori_id' => null,
            'barang_id' => null,
            'kuantitas' => 1,
            'hpp' => 0,
            'max_qty' => null,
        ];

        $index = count($this->details) - 1;
        $kategori = Kategori::where('name', 'Stok Sentrat')->first();

        $this->filteredBarangs[$index] = $kategori ? Barang::whereHas('jenis', fn($q) => $q->where('kategori_id', $kategori->id))->get()->map(fn($barang) => ['id' => $barang->id, 'name' => $barang->name])->toArray() : [];

        $this->calculateTotal();
    }

    public function removeDetail(int $index): void
    {
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
                        <x-input label="Rincian Transaksi" wire:model="name" placeholder="Contoh: Penjualan Sentrat" />
                        <x-choices-offline placeholder="Pilih Client" wire:model.live="client_id" :options="$clients"
                            single searchable clearable label="Client">
                            {{-- Tampilan item di dropdown --}} @scope('item', $clients)
                                <x-list-item :item="$clients" sub-value="invoice">
                                    <x-slot:actions>
                                        <x-badge :value="$clients->type ?? 'Tanpa Client'" class="badge-soft badge-secondary badge-sm" />

                                    </x-slot:actions>
                                </x-list-item>
                            @endscope

                            {{-- Tampilan ketika sudah dipilih --}}
                            @scope('selection', $clients)
                                {{ $clients->name . ' | ' . $clients->type }}
                            @endscope
                        </x-choices-offline>
                    </div>
                </div>
            </div>
        </x-card>

        <!-- SECTION: Detail Items -->
        <x-card>
            <div class="lg:grid grid-cols-8 gap-4">
                <div class="col-span-2">
                    <x-header title="Detail Items" subtitle="Tambah barang ke transaksi" size="text-2xl" />
                </div>
                <div class="col-span-6 grid gap-3">
                    @foreach ($details as $index => $item)
                    <x-choices-offline label="Kategori" wire:model.live="details.{{ $index }}.kategori_id"
                            :options="$kategoris" placeholder="Pilih Kategori" single clearable searchable />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end p-3 rounded-xl">
                            <x-choices-offline wire:model.live="details.{{ $index }}.barang_id" label="Barang"
                                :options="$filteredBarangs[$index] ?? []" placeholder="Pilih Barang" single clearable searchable />
                            <x-input label="Harga Jual" wire:model.live="details.{{ $index }}.value"
                                prefix="Rp " money="IDR" />
                            <x-input label="Qty (max {{ $item['max_qty'] ?? '-' }})"
                                wire:model.lazy="details.{{ $index }}.kuantitas" type="number" min="1"
                                step="0.01" :max="$item['max_qty'] ?? null" />
                            <x-input label="Total" :value="number_format(($item['value'] ?? 0) * ($item['kuantitas'] ?? 0), 0, '.', ',')" prefix="Rp" readonly />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end p-3 rounded-xl">
                            <x-input label="Barang" :value="$pokok->firstWhere('id', $item['barang_id'])?->name ?? '-'" readonly />
                            <x-input label="Harga Pokok (HPP)" :value="number_format(
                                $item['hpp'] ?? ($pokok->firstWhere('id', $item['barang_id'])?->hpp ?? 0),
                                0,
                                ',',
                                '.',
                            )" prefix="Rp" readonly />

                            <x-input label="Qty" :value="$item['kuantitas'] ?? 0" readonly />
                            <x-input label="Total HPP" :value="number_format(
                                ($item['hpp'] ?? ($pokok->firstWhere('id', $item['barang_id'])?->hpp ?? 0)) *
                                    ($item['kuantitas'] ?? 0),
                                0,
                                ',',
                                '.',
                            )" prefix="Rp" readonly />
                        </div>
                        <div class="flex justify-end">
                            <x-button spinner icon="o-trash" wire:click="removeDetail({{ $index }})"
                                class="btn-error btn-sm" label="Hapus Item" />
                        </div>
                    @endforeach

                    <div class="flex flex-wrap gap-3 justify-between items-center border-t pt-4">
                        <x-button spinner icon="o-plus" label="Tambah Item" wire:click="addDetail"
                            class="btn-primary" />
                        <x-input label="Total Pembayaran" :value="'Rp ' . number_format($total, 0, ',', '.')" readonly class="max-w-xs" />
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/sentrat-keluar" />
            <x-button spinner icon="o-check" label="Update" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
