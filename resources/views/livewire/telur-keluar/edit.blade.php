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

    public string $invoice = '';
    public string $invoice2 = '';
    public string $invoice3 = '';

    public string $name = '';
    public int $total = 0;
    public ?int $user_id = null;
    public ?int $client_id = null;
    public ?int $kategori_id = null;
    public ?string $tanggal = null;

    public array $details = [];
    public $barangs;
    public $pokok;
    public array $filteredBarangs = [];
    public ?int $totalPokok = 0;

    public function mount(Transaksi $transaksi): void
    {
        $this->transaksi = $transaksi->load('details');
        $telur = Transaksi::where('linked_id', $this->transaksi->id)->whereHas('kategori', fn($q) => $q->where('name', 'Stok Telur'))->first()->load('details');

        $this->invoice = $transaksi->invoice;
        $this->name = $transaksi->name;
        $this->user_id = $transaksi->user_id;
        $this->client_id = $transaksi->client_id;
        $this->kategori_id = $transaksi->kategori_id;
        $this->tanggal = \Carbon\Carbon::parse($transaksi->tanggal)->format('Y-m-d\TH:i:s');
        $this->total = $transaksi->total;

        $this->barangs = Barang::all();
        $this->pokok = Barang::all();

        // isi details
        foreach ($transaksi->details as $detail) {
            $this->details[] = [
                'barang_id' => $detail->barang_id,
                'value' => $detail->value,
                'kuantitas' => $detail->kuantitas,
                'max_qty' => (int)Barang::find($detail->barang_id)->stok + $detail->kuantitas,
                'hpp' => $telur->details->first()?->value,
            ];
        }

        $kategori = Kategori::where('name', 'Stok Telur')->first();
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
            'kategoris' => Kategori::where('name', 'like', '%Telur%')->where('type', 'like', '%Pendapatan%')->get(),
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

    public function updatedDetails($value, $key): void
    {
        if (str_ends_with($key, '.barang_id')) {
            $index = (int) explode('.', $key)[0];
            $barang = Barang::find($value);
            if ($barang) {
                $this->details[$index]['max_qty'] = $barang->stok;
                $this->details[$index]['kuantitas'] = max(1, (int) ($this->details[$index]['kuantitas'] ?? 1));
                $this->details[$index]['hpp'] = 0;
            }
        }

        if (str_ends_with($key, '.kuantitas')) {
            $index = (int) explode('.', $key)[0];
            $qty = (int) ($value ?: 1);
            $maxQty = $this->details[$index]['max_qty'] ?? null;
            if ($maxQty !== null && $qty > $maxQty) {
                $qty = $maxQty; // ✅ batasi qty sesuai stok
            }
            $this->details[$index]['kuantitas'] = $qty;
        }

        if (str_ends_with($key, '.value') || str_ends_with($key, '.kuantitas') || str_ends_with($key, '.hpp')) {
            $this->calculateTotal();
        }
    }

    private function calculateTotal(): void
    {
        $this->total = collect($this->details)->sum(fn($item) => ((int) ($item['value'] ?? 0)) * ((int) ($item['kuantitas'] ?? 1)));

        $this->totalPokok = collect($this->details)->sum(function ($item) {
            if (!$item['barang_id']) {
                return 0;
            }
            $barang = Barang::find($item['barang_id']);
            $hpp = isset($item['hpp']) && $item['hpp'] > 0 ? (float) $item['hpp'] : (float) ($barang->hpp ?? 0);
            $qty = (int) ($item['kuantitas'] ?? 0);
            return $hpp * $qty;
        });
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required',
            'details' => 'required|array|min:1',
            'details.*.barang_id' => 'required|exists:barangs,id',
            'details.*.value' => 'required|numeric|min:0',
            'details.*.kuantitas' => 'required|integer|min:1',
        ]);

        foreach ($this->details as $i => $item) {
            if ($item['max_qty'] !== null && $item['kuantitas'] > $item['max_qty']) {
                $this->addError("details.$i.kuantitas", 'Qty tidak boleh melebihi stok barang.');
                return;
            }
        }

        $hppTransaksi = Transaksi::where('linked_id', $this->transaksi->id)->whereHas('kategori', fn($q) => $q->where('name', 'HPP'))->first();
        $stokTransaksi = Transaksi::where('linked_id', $this->transaksi->id)->whereHas('kategori', fn($q) => $q->where('name', 'Stok Telur'))->first();

        $kategoriTelur = Kategori::where('name', 'Stok Telur')->first();
        $kategoriHpp = Kategori::where('name', 'HPP')->first();

        // Hitung total dan detail transaksi
        $totalTransaksi = 0;
        $detailData = [];

        foreach ($this->details as $item) {
            $detailQuery = DetailTransaksi::where('barang_id', $item['barang_id'])->whereHas('transaksi', function ($q) {
                $q->whereHas('kategori', fn($q2) => $q2->where('name', 'Stok Telur'))->where('type', 'Debit');
            });

            $totalHarga = $detailQuery->sum(\DB::raw('value * kuantitas'));
            $totalQty = $detailQuery->sum('kuantitas');
            $hargaSatuan = $totalQty > 0 ? $totalHarga / $totalQty : $item['value'];

            $totalTransaksi += ($item['hpp'] ?? $hargaSatuan) * ($item['kuantitas'] ?? 1);

            $detailData[] = [
                'barang_id' => $item['barang_id'],
                'kuantitas' => $item['kuantitas'] ?? 1,
                'value' => $item['hpp'] ?? $hargaSatuan,
            ];
        }

        // === 1. Update / Create Transaksi HPP ===
        if ($kategoriHpp) {
            $hppTransaksi->update([
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'client_id' => $this->client_id,
                'type' => 'Debit',
                'total' => $totalTransaksi,
            ]);

            // Replace detail
            $hppTransaksi->details()->delete();
            foreach ($detailData as $d) {
                DetailTransaksi::create(array_merge($d, ['transaksi_id' => $hppTransaksi->id]));
            }
        }

        // === 2. Update / Create Transaksi Stok Telur (Kredit) ===
        if ($kategoriTelur) {
            $stokTransaksi->update([
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'client_id' => $this->client_id,
                'type' => 'Kredit',
                'total' => $totalTransaksi,
            ]);

            // === Reset stok dulu berdasarkan transaksi lama ===
            if ($stokTransaksi) {
                foreach ($stokTransaksi->details as $oldDetail) {
                    $barang = Barang::find($oldDetail->barang_id);
                    if ($barang) {
                        // kembalikan stok sesuai kuantitas lama
                        $barang->increment('stok', $oldDetail->kuantitas);
                    }
                }
            }

            // === Hapus detail lama & replace dengan yang baru ===
            $stokTransaksi->details()->delete();

            foreach ($detailData as $d) {
                $stokTransaksi->details()->create($d);

                // kurangi stok sesuai kuantitas baru
                $barang = Barang::find($d['barang_id']);
                if ($barang) {
                    $barang->decrement('stok', $d['kuantitas']);
                }
            }
        }

        // === 3. Update Transaksi Pendapatan (Kredit Utama) ===
        $this->transaksi->update([
            'name' => $this->name,
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'kategori_id' => $this->kategori_id,
            'tanggal' => $this->tanggal,
            'total' => $this->total,
            'type' => 'Kredit',
        ]);

        // Replace detail pendapatan
        $this->transaksi->details()->delete();
        foreach ($this->details as $item) {
            DetailTransaksi::create([
                'transaksi_id' => $this->transaksi->id,
                'barang_id' => $item['barang_id'],
                'value' => $item['value'],
                'kuantitas' => $item['kuantitas'],
            ]);
        }
        $this->success('Transaksi berhasil diupdate!', redirectTo: '/telur-keluar');
    }

    public function addDetail(): void
    {
        $this->details[] = [
            'value' => 0,
            'barang_id' => null,
            'kuantitas' => 1,
            'hpp' => 0,
            'max_qty' => null,
        ];

        $index = count($this->details) - 1;
        $kategori = Kategori::where('name', 'Stok Telur')->first();

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
                    <x-select wire:model="kategori_id" label="Kategori" :options="$kategoris"
                        placeholder="Pilih Kategori" />
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
                        <x-select wire:model.lazy="details.{{ $index }}.barang_id" label="Barang"
                            :options="$filteredBarangs[$index] ?? []" placeholder="Pilih Barang" />
                        <x-input label="Value" wire:model.live="details.{{ $index }}.value" prefix="Rp "
                            money="IDR" />
                        <x-input label="Qty (max {{ $item['max_qty'] ?? '-' }})"
                            wire:model.lazy="details.{{ $index }}.kuantitas" type="number" min="1"
                            :max="$item['max_qty'] ?? null" />
                        <x-input label="Satuan" :value="$barangs->firstWhere('id', $item['barang_id'])?->satuan->name ?? '-'" readonly />
                    </div>
                    <div class="grid grid-cols-4 gap-2 items-center">
                        <x-input label="Harga Standart" :value="number_format($pokok->firstWhere('id', $item['barang_id'])?->hpp ?? 0)" readonly />
                        <x-input label="HPP" wire:model.live="details.{{ $index }}.hpp" prefix="Rp "
                            money="IDR" />
                        <x-input label="Qty" :value="$item['kuantitas'] ?? 0" readonly />
                        <x-input label="Total" :value="number_format(
                            ($item['hpp'] ?? ($pokok->firstWhere('id', $item['barang_id'])?->hpp ?? 0)) *
                                ($item['kuantitas'] ?? 0),
                            0,
                            '.',
                            ',',
                        )" prefix="Rp" readonly />
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
