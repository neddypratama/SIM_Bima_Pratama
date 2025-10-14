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

    #[Rule('required')]
    public ?int $kategori_id = null;

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
                    $q->where('type', 'like', '%Pendapatan%')->orWhere('type', 'like', '%Aset%');
                })
                ->get(),
            'clients' => Client::where('type', 'like', '%Pedagang%')->orWhere('type', 'like', '%Peternak%')->get(),
        ];
    }

    public function mount(Transaksi $transaksi): void
    {
        $this->transaksi = $transaksi;

        $this->invoice = $transaksi->invoice;
        $this->name = $transaksi->name;
        $this->user_id = $transaksi->user_id;
        $this->client_id = $transaksi->client_id;
        $this->kategori_id = $transaksi->details->first()?->kategori_id;
        $this->tanggal = Carbon::parse($transaksi->tanggal)->format('Y-m-d\TH:i:s');
        $this->total = $transaksi->total;

        $this->barangs = Barang::all();

        $this->details = $transaksi->details
            ->map(
                fn($d) => [
                    'barang_id' => $d->barang_id,
                    'value' => $d->value,
                    'kuantitas' => $d->kuantitas,
                ],
            )
            ->toArray();

        // Set filteredBarangs per detail
        foreach ($this->details as $index => $detail) {
            $this->filteredBarangs[$index] = Barang::whereHas('jenis', fn($q) => $q->where('kategori_id', $this->kategori_id))
                ->get()
                ->map(
                    fn($barang) => [
                        'id' => $barang->id,
                        'name' => $barang->name,
                    ],
                )
                ->toArray();
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

    public function updatedKategoriId($value): void
    {
        foreach ($this->details as $index => $detail) {
            $this->filteredBarangs[$index] = Barang::whereHas('jenis', fn($q) => $q->where('kategori_id', $value))->get()->map(fn($barang) => ['id' => $barang->id, 'name' => $barang->name])->toArray();

            $this->details[$index]['barang_id'] = null;
        }
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
            $this->filteredBarangs[$index] = Barang::whereHas('jenis', fn($q) => $q->where('kategori_id', $this->kategori_id))->get()->map(fn($barang) => ['id' => $barang->id, 'name' => $barang->name])->toArray();
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

    public function save(): void
    {
        $this->validate([
            'details.*.barang_id' => 'required|exists:barangs,id',
            'details.*.value' => 'required|numeric|min:0',
            'details.*.kuantitas' => 'required|integer|min:1',
        ]);

        // 1️⃣ Rollback stok lama
        foreach ($this->transaksi->details as $oldDetail) {
            $barang = Barang::find($oldDetail->barang_id);
            if (!$barang) {
                continue;
            }

            // Kurangi stok
            $stokBaru = max(0, $barang->stok - $oldDetail->kuantitas);
            $barang->update(['stok' => $stokBaru]);

            // Hitung ulang HPP dari semua transaksi lama (kecuali transaksi yang sedang diedit)
            $detailQuery = DetailTransaksi::where('barang_id', $barang->id)->where('transaksi_id', '!=', $this->transaksi->id)->whereHas('transaksi', fn($q) => $q->where('type', 'Debit'));

            $totalHarga = $detailQuery->sum(\DB::raw('value * kuantitas'));
            $totalQty = $detailQuery->sum('kuantitas');

            $hppBaru = $totalQty > 0 ? $totalHarga / $totalQty : 0;
            $barang->update(['hpp' => $hppBaru]);
        }

        // 2️⃣ Update transaksi
        $this->transaksi->update([
            'name' => $this->name,
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'kategori_id' => $this->kategori_id,
            'tanggal' => $this->tanggal,
            'total' => $this->total,
            'type' => 'Debit',
        ]);

        // 3️⃣ Hapus detail lama
        $this->transaksi->details()->delete();

        // 4️⃣ Simpan detail baru + update stok & HPP
        foreach ($this->details as $item) {
            DetailTransaksi::create([
                'transaksi_id' => $this->transaksi->id,
                'kategori_id' => $this->kategori_id,
                'value' => (int) $item['value'],
                'barang_id' => $item['barang_id'] ?? null,
                'kuantitas' => $item['kuantitas'] ?? null,
                'sub_total' => ((int) ($item['value'] ?? 0)) * ((int) ($item['kuantitas'] ?? 1)),
            ]);

            $barang = Barang::find($item['barang_id']);
            if (!$barang) {
                continue;
            }

            $stokLama = $barang->stok;
            $hppLama = $barang->hpp ?? 0;
            $qtyBaru = $item['kuantitas'] ?? 0;
            $hargaSatuanBaru = $item['value'] ?? 0;

            $totalHarga = $stokLama * $hppLama + $qtyBaru * $hargaSatuanBaru;
            $stokBaru = $stokLama + $qtyBaru;
            $hppBaru = $stokBaru > 0 ? $totalHarga / $stokBaru : 0;

            $barang->update([
                'stok' => $stokBaru,
                'hpp' => $hppBaru,
            ]);
        }

        $this->success('Transaksi berhasil diupdate!', redirectTo: '/telur-masuk');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Update Transaksi {{ $this->invoice }}" separator progress-indicator />

    <x-form wire:submit="save">
        <!-- SECTION: Basic Info -->
        <x-card>
            <div class="grid lg:grid-cols-8 gap-4">
                <div class="col-span-2">
                    <x-header title="Basic Info" subtitle="Buat transaksi baru" size="text-2xl" />
                </div>

                <div class="col-span-6 grid gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <x-input label="Invoice" wire:model="invoice" readonly />
                        <x-input label="User" :value="auth()->user()->name" readonly />
                        <x-datetime label="Tanggal & Waktu" wire:model="tanggal" icon="o-calendar"
                            type="datetime-local" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <x-input label="Rincian Transaksi" wire:model="name"
                                placeholder="Contoh: Pembelian Telur Ayam Ras" />
                        </div>
                        <x-choices-offline wire:model="client_id" label="Client" :options="$clients"
                            placeholder="Pilih Client" searchable single clearable />
                    </div>
                </div>
            </div>
        </x-card>

        <!-- SECTION: Detail Items -->
        <x-card>
            <div class="grid lg:grid-cols-8 gap-4">
                <div class="col-span-2">
                    <x-header title="Detail Barang" subtitle="Tambah barang ke transaksi" size="text-2xl" />
                </div>

                <div class="col-span-6 grid gap-5">
                    @foreach ($details as $index => $item)
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end p-3 rounded-xl">
                            <x-choices-offline wire:model.live="details.{{ $index }}.barang_id" label="Barang"
                                :options="$filteredBarangs[$index] ?? []" placeholder="Pilih Barang" searchable single clearable />
                            <x-input label="Harga Satuan" wire:model.live="details.{{ $index }}.value"
                                prefix="Rp" money="IDR" />
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
            <div class="flex flex-row sm:flex-row gap-2 justify-end">
                <x-button label="Batal" link="/telur-masuk" />
                <x-button spinner icon="o-check" label="Update" type="submit" class="btn-primary" />
            </div>
        </x-slot:actions>
    </x-form>
</div>
