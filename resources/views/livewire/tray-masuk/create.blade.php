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
    public ?int $client_id = null;

    #[Rule('required')]
    public ?int $kategori_id = null;

    public ?string $tanggal = null;

    #[Rule('required|array|min:1')]
    public array $details = [];

    // Semua barang
    public $barangs;

    // Barang yang difilter per detail
    public array $filteredBarangs = [];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'barangs' => $this->barangs,
            'clients' => Client::where('type', 'like', '%Pedagang%')
                ->orWhere('type', 'like', '%Peternak%')
                ->get(),
        ];
    }

    public function mount(): void
    {
        $this->user_id = auth()->id();
        $this->tanggal = now()->format('Y-m-d\TH:i');
        $this->updatedTanggal($this->tanggal);

        $this->barangs = Barang::all();

        $kategori = Kategori::where('name', 'Stok Tray')->first();
        $this->kategori_id = $kategori->id;
        if ($kategori) {
            $this->kategori_id = $kategori->id;

            // Pastikan minimal ada satu detail
            if (empty($this->details)) {
                $this->details[] = [
                    'barang_id' => null,
                    'value' => 0,
                    'kuantitas' => 1,
                ];
            }

            // Load filteredBarangs per detail
            foreach ($this->details as $index => $detail) {
                $this->filteredBarangs[$index] = Barang::whereHas('jenis', fn($q) => $q->where('kategori_id', $kategori->id))
                    ->get()
                    ->map(
                        fn($barang) => [
                            'id' => $barang->id,
                            'name' => $barang->name,
                        ],
                    )
                    ->toArray();

                // Reset barang_id agar user pilih ulang
                $this->details[$index]['barang_id'] = null;
            }
        }
    }

    public function updatedTanggal($value): void
    {
        if ($value) {
            $tanggal = \Carbon\Carbon::parse($value)->format('Ymd');
            $str = Str::upper(Str::random(4));
            $this->invoice = 'INV-' . $tanggal . '-TRY-' . $str;
            $this->invoice2 = 'INV-' . $tanggal . '-TNI-' . $str;
        }
    }

    public function updatedDetails($value, $key): void
    {
        // Hitung total jika value/qty berubah
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
        // ✅ Validasi seluruh input sekaligus
        $this->validate();

        $this->validate([
            'details.*.value' => 'required|numeric|min:0',
            'details.*.barang_id' => 'required|exists:barangs,id',
            'details.*.kuantitas' => 'required|integer|min:1',
        ]);

        $stok = Transaksi::create([
            'invoice' => $this->invoice,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'kategori_id' => $this->kategori_id,
            'client_id' => $this->client_id,
            'type' => 'Debit',
            'total' => $this->total,
        ]);

        foreach ($this->details as $item) {
            DetailTransaksi::create([
                'transaksi_id' => $stok->id,
                'kategori_id' => $this->kategori_id,
                'value' => (int) $item['value'], // harga satuan
                'barang_id' => $item['barang_id'] ?? null,
                'kuantitas' => $item['kuantitas'] ?? null,
                'sub_total' => ((int) $item['value'] ?? 0) * ((int) $item['kuantitas'] ?? 1),
            ]);

            $barang = Barang::find($item['barang_id']);

            // Hitung HPP baru
            $stokLama = $barang->stok;
            $hppLama = $barang->hpp ?? 0; // default 0 jika belum ada HPP
            $qtyBaru = $item['kuantitas'] ?? 0;
            $hargaSatuanBaru = $item['value'] ?? 0;

            $totalHarga = $stokLama * $hppLama + $qtyBaru * $hargaSatuanBaru;
            $stokBaru = $stokLama + $qtyBaru;
            $hppBaru = $stokBaru > 0 ? $totalHarga / $stokBaru : 0;

            // Update stok dan HPP barang
            $barang->update([
                'stok' => $stokBaru,
                'hpp' => $hppBaru,
            ]);
        }

        $this->success('Transaksi berhasil dibuat!', redirectTo: '/tray-masuk');
    }

    public function addDetail(): void
    {
        $this->details[] = [
            'value' => 0,
            'barang_id' => null,
            'kuantitas' => 1,
        ];

        $index = count($this->details) - 1;

        // Jika kategori sudah dipilih, filter barang sesuai kategori
        if ($this->kategori_id) {
            $kategori = Kategori::find($this->kategori_id);
            $this->filteredBarangs[$index] = Barang::whereHas('jenis', fn($q) => $q->where('kategori_id', $kategori->id))
                ->get()
                ->map(
                    fn($barang) => [
                        'id' => $barang->id,
                        'name' => $barang->name,
                    ],
                )
                ->toArray();
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
    <x-header title="Create Transaksi Pembelian Tray" separator progress-indicator />

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
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <x-input label="Rincian" wire:model="name" placeholder="Contoh: Pembelian Tray"/>
                        </div>
                        <x-choices-offline wire:model="client_id" label="Client" :options="$clients"
                            placeholder="Pilih Client" searchable single clearable />
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
                    @foreach ($details as $index => $item)
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end p-3 rounded-xl">
                            <x-choices-offline wire:model.live="details.{{ $index }}.barang_id" label="Barang"
                                :options="$filteredBarangs[$index] ?? []" placeholder="Pilih Barang" searchable single clearable />
                            <x-input label="Harga Satuan" wire:model.live="details.{{ $index }}.value" prefix="Rp "
                                money="IDR" />
                            <x-input label="Qty" wire:model.lazy="details.{{ $index }}.kuantitas"
                                type="number" min="1" />
                            <x-input label="Total" :value="number_format(($item['value'] ?? 0) * ($item['kuantitas'] ?? 0), 0, '.', ',')" prefix="Rp" readonly />
                        </div>
                        
                        <div class="flex justify-end">
                            <x-button spinner icon="o-trash" wire:click="removeDetail({{ $index }})"
                                class="btn-error btn-sm" label="Hapus Item" />
                        </div>
                    @endforeach

                    <div class="flex flex-wrap gap-3 justify-between items-center border-t pt-4">
                        <x-button spinner icon="o-plus" label="Tambah Item" wire:click="addDetail"
                            class="btn-primary" />
                        <x-input label="Total Pembelian" :value="'Rp ' . number_format($total, 0, ',', '.')" readonly class="max-w-xs" />
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/tray-masuk" />
            <x-button spinner label="Save" icon="o-paper-airplane" spinner="save" type="submit"
                class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
