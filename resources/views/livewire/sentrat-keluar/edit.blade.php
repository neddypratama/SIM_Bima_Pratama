<?php

use Livewire\Volt\Component;
use App\Models\StokBatch;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Client;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

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

    private function hitungHppFifoEdit(int $barangId, float $qty): float
    {
        $sisa = $qty;
        $total = 0;

        // AMBIL SEMUA BATCH (TANPA FILTER qty_sisa)
        $batches = StokBatch::where('barang_id', $barangId)->orderBy('tanggal')->orderBy('id')->get();

        foreach ($batches as $batch) {
            if ($sisa <= 0) {
                break;
            }

            $ambil = min($batch->qty_masuk, $sisa);

            $total += $ambil * $batch->harga;
            $sisa -= $ambil;
        }

        return $qty > 0 ? round($total / $qty, 2) : 0;
    }

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

        foreach ($transaksi->details as $detail) {
            // stok sekarang (SETELAH transaksi lama)
            $stokSekarang = StokBatch::where('barang_id', $detail->barang_id)->sum('qty_sisa');

            // max qty = stok sekarang + qty transaksi lama
            $maxQty = $stokSekarang + $detail->kuantitas;

            // hitung ulang HPP FIFO (READ ONLY)
            $hpp = $this->hitungHppFifoEdit($detail->barang_id, $detail->kuantitas);

            $this->details[] = [
                'kategori_id' => $detail->kategori_id,
                'barang_id' => $detail->barang_id,
                'value' => $detail->value,
                'kuantitas' => $detail->kuantitas,
                'max_qty' => $maxQty,
                'hpp' => $hpp,
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
            'kategoris' => Kategori::where('name', 'like', '%Pakan%')
                ->where('name', 'not like', 'Pakan Curah')
                ->whereHas('detailKategori', function (Builder $q) {
                    $q->where(function ($q) {
                        $q->where('type', 'like', '%Pendapatan%');
                    });
                })
                ->get(),
            'clients' => Client::where('type', 'like', '%Pedagang%')->orWhere('type', 'like', '%Peternak%')->get(),
        ];
    }

    public function updatedDetails($value, $key): void
    {
        // --- Jika kategori dipilih ---
        if (str_ends_with($key, '.kategori_id')) {
            $index = explode('.', $key)[0];
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
            $index = explode('.', $key)[0];
            $barang = Barang::find($value);
            $stok = StokBatch::where('barang_id', $value)->get()->sum('qty_sisa') ?? 0;

            if ($barang) {
                $this->details[$index]['max_qty'] = $stok;
                $this->details[$index]['kuantitas'] = max(0.01, $this->details[$index]['kuantitas'] ?? 1);
            }
        }

        // --- Jika qty diubah ---
        if (str_ends_with($key, '.kuantitas')) {
            $index = explode('.', $key)[0];
            $qty = max(0.01,  $value);
            $maxQty = $this->details[$index]['max_qty'] ?? 0;

            if ($qty > $maxQty) {
                $qty = $maxQty;
            }

            $this->details[$index]['kuantitas'] = $qty;

            if (!empty($this->details[$index]['barang_id'])) {
                $this->details[$index]['hpp'] = $this->hitungHppFifoEdit($this->details[$index]['barang_id'], $qty);
            }
        }

        if (str_ends_with($key, '.value') || str_ends_with($key, '.kuantitas') || str_ends_with($key, '.hpp')) {
            $this->calculateTotal();
        }
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

    private function fifoOut(int $barangId, float $qty): void
    {
        $batches = StokBatch::where('barang_id', $barangId)->where('qty_sisa', '>', 0)->orderBy('tanggal')->orderBy('id')->lockForUpdate()->get();

        foreach ($batches as $batch) {
            if ($qty <= 0) {
                break;
            }

            $ambil = min($batch->qty_sisa, $qty);
            $batch->decrement('qty_sisa', $ambil);
            $qty -= $ambil;
        }

        if ($qty > 0) {
            throw new \Exception('Stok tidak mencukupi (FIFO OUT gagal)');
        }
    }

    private function rollbackStokLama(): void
    {
        foreach ($this->transaksi->details as $detail) {
            $qty = $detail->kuantitas;

            $batches = StokBatch::where('barang_id', $detail->barang_id)->orderByDesc('tanggal')->orderByDesc('id')->lockForUpdate()->get();

            foreach ($batches as $batch) {
                if ($qty <= 0) {
                    break;
                }

                $ruang = $batch->qty_masuk - $batch->qty_sisa;
                $kembali = min($ruang, $qty);

                $batch->increment('qty_sisa', $kembali);
                $qty -= $kembali;
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'details' => 'required|array|min:1',
            'details.*.barang_id' => 'required|exists:barangs,id',
            'details.*.value' => 'required|numeric|min:0',
            'details.*.kuantitas' => 'required|numeric|min:0.01',
            'details.*.kategori_id' => 'required|exists:kategoris,id',
        ]);

        foreach ($this->details as $i => $item) {
            if ($item['max_qty'] !== null && $item['kuantitas'] > $item['max_qty']) {
                $this->addError("details.$i.kuantitas", 'Qty tidak boleh melebihi stok barang.');
                return;
            }
        }

        DB::transaction(function () {
            $inv = substr($this->transaksi->invoice, -4);
            $part = explode('-', $this->transaksi->invoice);
            $tanggal = $part[1];

            $bonTransaksi = Transaksi::where('invoice', 'like', "%-$tanggal-BON-$inv")->first();
            $hppTransaksi = Transaksi::where('invoice', 'like', "%-$tanggal-HPP-$inv")->first();
            $stokTransaksi = Transaksi::where('invoice', 'like', "%-$tanggal-STR-$inv")->first();

            $kategoriPri = Kategori::where('name', 'Penjualan Pakan Curah')->first();
            $kategoriBon = Kategori::where('name', 'Piutang Peternak')->first();
            $kategoriSentrat = Kategori::where('name', 'Stok Pakan')->first();
            $kategoriHpp = Kategori::where('name', 'HPP')->first();

            // Hitung total dan detail transaksi
            $totalTransaksi = 0;
            $detailData = [];

            foreach ($this->details as $item) {
                $detailQuery = DetailTransaksi::where('barang_id', $item['barang_id'])->whereHas('transaksi', function ($q) {
                    $q->whereHas('details.kategori', fn($q2) => $q2->where('name', 'Stok Pakan'))->where('type', 'Debit');
                });

                $totalHarga = $detailQuery->sum(\DB::raw('value * kuantitas'));
                $totalQty = $detailQuery->sum('kuantitas');
                $hargaSatuan = $totalQty > 0 ? $totalHarga / $totalQty : $item['value'];

                $totalTransaksi += ($item['hpp'] ?? $hargaSatuan) * ($item['kuantitas'] ?? 1);

                $detailData[] = [
                    'barang_id' => $item['barang_id'],
                    'kuantitas' => $item['kuantitas'] ?? 1,
                    'value' => $item['hpp'] ?? $hargaSatuan,
                    'sub_total' => ($item['hpp'] ?? $hargaSatuan) * ($item['kuantitas'] ?? 1),
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
                    DetailTransaksi::create(array_merge($d, ['transaksi_id' => $hppTransaksi->id, 'kategori_id' => $kategoriHpp->id]));
                }
            }

            // === 2. Update / Create Transaksi Stok Sentrat (Kredit) ===
            if ($kategoriSentrat) {
                $stokTransaksi->update([
                    'name' => $this->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'client_id' => $this->client_id,
                    'type' => 'Kredit',
                    'total' => $totalTransaksi,
                ]);

                // === Hapus detail lama & replace dengan yang baru ===
                $stokTransaksi->details()->delete();

                foreach ($detailData as $d) {
                    $stokTransaksi->details()->create(array_merge($d, ['transaksi_id' => $stokTransaksi->id, 'kategori_id' => $kategoriSentrat->id]));

                }
            }

            $bonTransaksi->update([
                'name' => $this->name,
                'user_id' => $this->user_id,
                'client_id' => $this->client_id,
                'tanggal' => $this->tanggal,
                'total' => $this->total,
                'type' => 'Debit',
            ]);
            $bonTransaksi->details()->delete();
            foreach ($this->details as $item) {
                DetailTransaksi::create([
                    'transaksi_id' => $bonTransaksi->id,
                    'kategori_id' => $kategoriBon->id,
                    'barang_id' => $item['barang_id'],
                    'value' => $item['value'],
                    'kuantitas' => $item['kuantitas'],
                    'sub_total' => $item['value'] * $item['kuantitas'],
                ]);
            }

            // === 3. Update Transaksi Pendapatan (Kredit Utama) ===
            // 1️⃣ Ambil transaksi yang mau diedit
            $transaksi = Transaksi::findOrFail($this->transaksi->id);

            /** ===============================
             * 1. ROLLBACK STOK LAMA
             * =============================== */
            $this->rollbackStokLama();

            /** ===============================
             * 2. UPDATE TRANSAKSI UTAMA
             * =============================== */
            $transaksi->update([
                'invoice' => $this->invoice,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'client_id' => $this->client_id,
                'type' => 'Kredit', // kalau mau bisa dari input juga
                'total' => $this->total,
            ]);

            // 3️⃣ Rollback titipan lama (jika sebelumnya pernah masuk ke Supriyadi)
            $oldDetails = DetailTransaksi::where('transaksi_id', $transaksi->id)->whereHas('kategori', fn($q) => $q->where('name', 'Penjualan Pakan Curah'))->get();


            $this->transaksi->details()->delete();

            // 5️⃣ Insert detail baru
            $totalTitipanBaru = 0;
            foreach ($this->details as $item) {
                $hpp = $this->hitungHppFifoEdit($item['barang_id'], $item['kuantitas']);
                $subTotal = ($item['value'] - $item['hpp'] ?? 0) * ($item['kuantitas'] ?? 1);

                DetailTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'kategori_id' => $item['kategori_id'],
                    'value' => $item['value'],
                    'barang_id' => $item['barang_id'] ?? null,
                    'kuantitas' => $item['kuantitas'] ?? null,
                    'sub_total' => ($item['value'] ?? 0) * ($item['kuantitas'] ?? 1),
                ]);

                // mapping titipan jika kategori sesuai
                if ($item['kategori_id'] == $kategoriPri->id) {
                    $totalTitipanBaru += $subTotal;
                }

                // FIFO keluar stok
                $this->fifoOut($item['barang_id'], $item['kuantitas']);
            }
        });

        $this->success('Transaksi berhasil diupdate!', redirectTo: '/sentrat-keluar');
    }

    public function addDetail(): void
    {
        $this->details[] = [
            'value' => 0,
            'kategori_id' => null,
            'barang_id' => null,
            'kuantitas' => 0.01,
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
                        <x-datetime label="Date + Time" wire:model="tanggal" icon="o-calendar" type="datetime-local" step="1"/>
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
                                wire:model.lazy="details.{{ $index }}.kuantitas" type="number" min="0.01"
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
