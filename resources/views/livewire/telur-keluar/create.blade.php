<?php

use Livewire\Volt\Component;
use App\Models\Transaksi;
use App\Models\TransaksiLink;
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
    public string $invoice2 = '';
    public string $invoice3 = '';

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
    public $pokok;
    public $totalPokok;
    public float $harga_jual = 0;
    public array $filteredBarangs = [];
    public string $kas = '';

    public function with(): array
    {
        return [
            'pokok' => $this->pokok,
            'users' => User::all(),
            'barangs' => $this->barangs,
            'kategoris' => Kategori::where('name', 'like', '%Telur%')
                ->where(function ($q) {
                    $q->where('type', 'like', '%Pendapatan%')->orWhere('type', 'like', '%Pengeluaran%');
                })
                ->get(),
            'clients' => Client::where('type', 'like', '%Pedagang%')->get(),
        ];
    }

    public function mount(): void
    {
        $this->user_id = auth()->id();
        $this->tanggal = now()->format('Y-m-d\TH:i');
        $this->updatedTanggal($this->tanggal);

        $this->barangs = Barang::all();
        $this->pokok = Barang::all();

        $kategori = Kategori::where('name', 'Stok Telur')->first();
        if ($kategori) {
            if (empty($this->details)) {
                $this->details[] = [
                    'kategori_id' => null,
                    'barang_id' => null,
                    'value' => 0,
                    'kuantitas' => 1,
                    'hpp' => 0,
                    'max_qty' => null,
                ];
            }

            foreach ($this->details as $index => $detail) {
                $this->filteredBarangs[$index] = Barang::whereHas('jenis', fn($q) => $q->where('kategori_id', $kategori->id))->get()->map(fn($barang) => ['id' => $barang->id, 'name' => $barang->name])->toArray();
            }
        }
    }

    public function updatedTanggal($value): void
    {
        if ($value) {
            $tanggal = \Carbon\Carbon::parse($value)->format('Ymd');
            $str = Str::upper(Str::random(4));
            $this->invoice = 'INV-' . $tanggal . '-DPT-' . $str;
            $this->invoice2 = 'INV-' . $tanggal . '-TLR-' . $str;
            $this->invoice3 = 'INV-' . $tanggal . '-HPP-' . $str;
        }
    }

    public function updatedDetails($value, $key): void
    {
        // --- Jika kategori dipilih ---
        if (str_ends_with($key, '.kategori_id')) {
            $index = (int) explode('.', $key)[0];
            $kategori = Kategori::find($value);

            if ($kategori) {
                // Ambil nama setelah kata "Penjualan"
                $jenisNama = trim(preg_replace('/^Penjualan\s*/i', '', $kategori->name));

                // Filter barang yang memiliki jenis dengan nama tersebut
                $this->filteredBarangs[$index] = Barang::whereHas('jenis', function ($q) use ($jenisNama) {
                    $q->where('name', 'like', "%{$jenisNama}%");
                })
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

        // --- Jika barang dipilih ---
        if (str_ends_with($key, '.barang_id')) {
            $index = (int) explode('.', $key)[0];
            $barang = Barang::find($value);
            if ($barang) {
                $this->details[$index]['max_qty'] = $barang->stok;
                $this->details[$index]['kuantitas'] = max(1, (int) ($this->details[$index]['kuantitas'] ?? 1));
                $this->details[$index]['hpp'] = (float) $barang->hpp;
            }
        }

        // --- Jika qty diubah ---
        if (str_ends_with($key, '.kuantitas')) {
            $index = (int) explode('.', $key)[0];
            $qty = (int) ($value ?: 1);
            $maxQty = $this->details[$index]['max_qty'] ?? null;
            if ($maxQty !== null && $qty > $maxQty) {
                $qty = $maxQty;
            }
            $this->details[$index]['kuantitas'] = $qty;
        }

        // --- Update total jika ada perubahan harga/qty/hpp ---
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
        $this->validate();

        $this->validate([
            'details.*.value' => 'required|numeric|min:0',
            'details.*.barang_id' => 'required|exists:barangs,id',
            'details.*.kategori_id' => 'required|exists:kategoris,id',
            'details.*.kuantitas' => 'required|integer|min:1',
            'details.*.hpp' => 'required|numeric|min:0',
        ]);

        foreach ($this->details as $i => $item) {
            if ($item['max_qty'] !== null && $item['kuantitas'] > $item['max_qty']) {
                $this->addError("details.$i.kuantitas", 'Qty tidak boleh melebihi stok barang.');
                return;
            }
        }

        $kategoriTelur = Kategori::where('name', 'Stok Telur')->first();
        $kategoriHpp = Kategori::where('name', 'HPP')->first();

        // dd($kategoriTelur, $kategoriHpp);

        $totalTransaksi = 0;
        $detailData = [];

        $transaksi = Transaksi::create([
            'invoice' => $this->invoice,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'client_id' => $this->client_id,
            'type' => 'Kredit',
            'total' => $this->total,
        ]);

        foreach ($this->details as $item) {
            DetailTransaksi::create([
                'transaksi_id' => $transaksi->id,
                'kategori_id' => $item['kategori_id'] ?? null,
                'value' => (int) $item['value'], // harga satuan
                'barang_id' => $item['barang_id'] ?? null,
                'kuantitas' => $item['kuantitas'] ?? null,
                'sub_total' => ((int) ($item['value'] ?? 0)) * ((int) ($item['kuantitas'] ?? 1)), // total harga (harga satuan * qty
            ]);
        }

        foreach ($this->details as $item) {
            $detailQuery = DetailTransaksi::where('barang_id', $item['barang_id'])->whereHas('transaksi', function ($q) {
                $q->whereHas('details.kategori', fn($q2) => $q2->where('name', 'Stok Telur'))->where('type', 'Debit');
            });

            $totalHarga = $detailQuery->sum(\DB::raw('value * kuantitas'));
            $totalQty = $detailQuery->sum('kuantitas');
            $hargaSatuan = $totalQty > 0 ? $totalHarga / $totalQty : $item['value'];

            $totalTransaksi += ($item['hpp'] ?? $hargaSatuan) * ($item['kuantitas'] ?? 1);

            $detailData[] = [
                'barang_id' => $item['barang_id'],
                'kuantitas' => $item['kuantitas'] ?? 1,
                'value' => $item['hpp'] ?? $hargaSatuan,
                'sub_total' => ((int) ($item['hpp'] ?? 0)) * ((int) ($item['kuantitas'] ?? 1)),
            ];
        }

        if ($kategoriHpp) {
            $hpp = Transaksi::create([
                'invoice' => $this->invoice3,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'kategori_id' => $kategoriHpp->id,
                'client_id' => $this->client_id,
                'type' => 'Debit',
                'total' => $totalTransaksi,
            ]);

            foreach ($detailData as $d) {
                DetailTransaksi::create(array_merge($d, ['transaksi_id' => $hpp->id, 'kategori_id' => $kategoriHpp->id]));
            }
        }

        $stok = Transaksi::create([
            'invoice' => $this->invoice2,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'client_id' => $this->client_id,
            'type' => 'Kredit',
            'total' => $totalTransaksi,
        ]);

        foreach ($detailData as $d) {
            DetailTransaksi::create(array_merge($d, ['transaksi_id' => $stok->id, 'kategori_id' => $kategoriTelur->id]));

            $barang = Barang::find($d['barang_id']);
            if ($barang) {
                $barang->decrement('stok', $d['kuantitas']);
            }
        }

        TransaksiLink::create([
            'transaksi_id' => $hpp->id,
            'linked_id' => $stok->id,
        ]);

        TransaksiLink::create([
            'transaksi_id' => $stok->id,
            'linked_id' => $hpp->id,
        ]);

        $this->success('Transaksi berhasil dibuat!', redirectTo: '/telur-keluar');
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
    <x-header title="Create Transaksi Penjualan Telur" separator progress-indicator />

    <x-form wire:submit="save">
        <x-card>
            <div class="grid lg:grid-cols-8 gap-4">
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
                        <div class="sm:col-span-2">
                            <x-input label="Rincian Transaksi" wire:model="name"
                                placeholder="Contoh: Penjualan Telur Ayam Ras" />
                        </div>
                        <x-choices-offline placeholder="Pilih Client" wire:model.live="client_id" :options="$clients"
                            single searchable clearable label="Client" >
                            {{-- Tampilan item di dropdown --}} @scope('item', $clients)
                                <x-list-item :item="$clients" sub-value="invoice">
                                <x-slot:avatar>
                                    <x-icon name="fas.user" class="bg-primary/10 p-2 w-9 h-9 rounded-full" />
                                </x-slot:avatar>
                                <x-slot:actions>
                                    <x-badge :value="$clients->type ?? 'Tanpa Client'" class="badge-soft badge-secondary badge-sm" />

                                </x-slot:actions>
                                </x-list-item>
                            @endscope

                            {{-- Tampilan ketika sudah dipilih --}}
                            @scope('selection', $clients)
                                {{ $clients->name . ' | ' .  $clients->type}}
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
                        <x-select wire:model.live="details.{{ $index }}.kategori_id" label="Kategori"
                            :options="$kategoris" placeholder="Pilih Kategori" />
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end p-3 rounded-xl">
                            <x-choices-offline wire:model.live="details.{{ $index }}.barang_id" label="Barang"
                                :options="$filteredBarangs[$index] ?? []" placeholder="Pilih Barang" single clearable searchable />
                            <x-input label="Harga Jual" wire:model.live="details.{{ $index }}.value"
                                prefix="Rp " money="IDR" />
                            <x-input label="Qty (max {{ $item['max_qty'] ?? '-' }})"
                                wire:model.lazy="details.{{ $index }}.kuantitas" type="number" min="1"
                                :max="$item['max_qty'] ?? null" />
                            <x-input label="Total" :value="number_format(($item['value'] ?? 0) * ($item['kuantitas'] ?? 0), 0, '.', ',')" prefix="Rp" readonly />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end p-3 rounded-xl">
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
            <x-button spinner label="Cancel" link="/telur-keluar" />
            <x-button spinner label="Save" icon="o-paper-airplane" spinner="save" type="submit"
                class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
