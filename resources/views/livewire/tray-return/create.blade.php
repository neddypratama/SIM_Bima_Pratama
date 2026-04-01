<?php

use Livewire\Volt\Component;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Client;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

new class extends Component {
    use Toast;

    #[Rule('required|unique:transaksis,invoice')]
    public string $invoice = '';

    #[Rule('required')]
    public string $name = '';

    #[Rule('required')]
    public ?int $user_id = null;

    #[Rule('required')]
    public ?int $client_id = null;

    public ?int $transaksis_id = null;
    public ?Transaksi $transaksi = null;

    public ?string $tanggal = null;

    #[Rule('required|array|min:1')]
    public array $details = [];

    public float $total = 0;

    public function with(): array
    {
        return [
            'transaksis' => Transaksi::where('type', 'Kredit')->whereHas('details.kategori', fn($q) => $q->where('name', 'like', 'Penjualan Eggtray%'))->get()->map(
                fn($t) => [
                    'id' => $t->id,
                    'name' => "{$t->invoice} | {$t->name}",
                ],
            ),
        ];
    }

    /**
     * 🔥 LOAD DATA SAAT PILIH TRANSAKSI
     */
    public function updatedTransaksisId($value): void
    {
        if (!$value) {
            return;
        }

        $transaksi = Transaksi::with('details')->findOrFail($value);

        $this->transaksi = $transaksi;
        $this->user_id = auth()->id();
        $this->client_id = $transaksi->client_id;
        $this->name = 'Retur dari ' . $transaksi->invoice;

        // generate invoice baru
        $tanggal = now()->format('Ymd');
        $this->invoice = 'INV-' . $tanggal . '-RTN-' . Str::upper(Str::random(4));

        $this->tanggal = now()->format('Y-m-d\TH:i:s');

        $this->details = [];

        foreach ($transaksi->details as $detail) {
            $this->details[] = [
                'barang_id' => $detail->barang_id,
                'kategori_id' => $detail->kategori_id,

                // 🔥 dari transaksi asal
                'value' => $detail->value,
                'kuantitas' => 0,

                // 🔥 MAX dari transaksi
                'max_qty' => $detail->kuantitas,
            ];
        }

        $this->calculateTotal();
    }

    /**
     * 🔥 VALIDASI QTY
     */
    public function updatedDetails($value, $key): void
    {
        if (str_ends_with($key, '.kuantitas')) {
            $index = explode('.', $key)[0];

            $qty = (float) $value;
            $max = $this->details[$index]['max_qty'];

            if ($qty > $max) {
                $this->details[$index]['kuantitas'] = $max;
                $this->warning("Qty tidak boleh melebihi {$max}");
            }
        }

        $this->calculateTotal();
    }

    private function calculateTotal(): void
    {
        $this->total = collect($this->details)->sum(fn($d) => ($d['value'] ?? 0) * ($d['kuantitas'] ?? 0));
    }

    /**
     * 🔥 SAVE RETUR
     */
    public function save(): void
    {
        $this->validate();

        foreach ($this->details as $item) {
            if ($item['kuantitas'] > $item['max_qty']) {
                $this->error('Qty melebihi transaksi asli');
                return;
            }
        }

        $transaksi = Transaksi::create([
            'invoice' => $this->invoice,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'client_id' => $this->client_id,
            'type' => 'Debit', // ✅ RETUR = DEBIT
            'total' => $this->total,
            'status' => 'Perbaikan',
        ]);

        foreach ($this->details as $item) {
            DetailTransaksi::create([
                'transaksi_id' => $transaksi->id,
                'kategori_id' => $item['kategori_id'],
                'barang_id' => $item['barang_id'],
                'value' => $item['value'],
                'kuantitas' => $item['kuantitas'],
                'sub_total' => $item['value'] * $item['kuantitas'],
            ]);
        }

        $this->success('Retur berhasil dibuat!', redirectTo: '/tray-return');
    }
};
?>

<div class="p-4 space-y-6">

    <x-header title="Create Retur Penjualan" separator progress-indicator />

    <x-form wire:submit="save">

        <x-card>
            <div class="grid lg:grid-cols-8 gap-4">
                <div class="col-span-2">
                    <x-header title="Pilih Transaksi" subtitle="Pilih transaksi penjualan terlebih dahulu"
                        size="text-2xl" />
                </div>

                <div class="col-span-6">
                    <x-choices-offline wire:model.live="transaksis_id" label="Transaksi" :options="$transaksis"
                        placeholder="Pilih Transaksi Penjualan" single searchable clearable />
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="grid lg:grid-cols-8 gap-4">
                <div class="col-span-2">
                    <x-header title="Basic Info" subtitle="Buat transaksi baru" size="text-2xl" />
                </div>
                <div class="col-span-6 grid gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <x-input label="Invoice" wire:model="invoice" readonly />
                        <x-input label="User" :value="auth()->user()->name" readonly />
                        <x-datetime label="Date + Time" wire:model="tanggal" icon="o-calendar" type="datetime-local"
                            step="1" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input label="Keterangan" wire:model="name" />
                        <x-input label="Client" :value="$transaksi?->client->name ?? '-'" readonly />
                    </div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="lg:grid grid-cols-8 gap-4">
                <div class="col-span-2">
                    <x-header title="Detail Items" subtitle="Tambah barang ke transaksi" size="text-2xl" />
                </div>
                <div class="col-span-6 grid gap-3">
                    @foreach ($details as $i => $item)
                        <div class="grid grid-cols-4 gap-3 mb-3">

                            <x-input label="Barang" :value="\App\Models\Barang::find($item['barang_id'])->name ?? '-'" readonly />

                            <x-input label="Harga" :value="number_format($item['value'], 0, ',', '.')" prefix="Rp" readonly />

                            <x-input label="Qty (max {{ $item['max_qty'] }})"
                                wire:model.lazy="details.{{ $i }}.kuantitas" type="number" min="0.01"
                                step="0.01" />

                            <x-input label="Total" :value="number_format($item['value'] * $item['kuantitas'], 0, ',', '.')" prefix="Rp" readonly />

                        </div>
                    @endforeach

                    <div class="gap-3 border-t pt-4">
                        <x-input label="Total Pembayaran" :value="'Rp ' . number_format($total, 0, ',', '.')" readonly class="max-w-xs" />
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button label="Cancel" link="/tray-return" />
            <x-button label="Save" type="submit" class="btn-primary" />
        </x-slot:actions>

    </x-form>
</div>
