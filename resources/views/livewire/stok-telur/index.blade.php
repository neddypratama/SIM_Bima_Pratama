<?php

use App\Models\Stok;
use App\Models\StokBatch;
use App\Models\StokKeluarBatch;
use App\Models\Barang;
use App\Models\Transaksi;
use App\Models\Kategori;
use App\Models\DetailTransaksi;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exports\StokTelurExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

new class extends Component {
    use Toast;
    use WithPagination;

    public $today;
    public function mount(): void
    {
        $this->today = \Carbon\Carbon::today();
    }

    public string $search = '';
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];
    public int $filter = 0;
    public int $barang_id = 0;

    public bool $exportModal = false; // ✅ Modal export
    // ✅ Tambah tanggal untuk filter export
    public ?string $startDate = null;
    public ?string $endDate = null;

    public ?string $selectedId = null;
    public ?string $selectedInv = null;
    public bool $statusModal = false;
    public ?string $status = null;

    public $page = [['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50'], ['id' => 100, 'name' => '100'], ['id' => 500, 'name' => '500']];

    public int $perPage = 25; // Default jumlah data per halaman
    public function clear(): void
    {
        $this->reset(['search', 'barang_id', 'filter', 'startDate', 'endDate']);
        $this->resetPage();
        $this->success('Filters cleared.', position: 'toast-top');
    }

    public function openExportModal(): void
    {
        $this->exportModal = true;
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
    }

    public function export(): mixed
    {
        if (!$this->startDate || !$this->endDate) {
            $this->error('Pilih tanggal terlebih dahulu.');
            return null; // ✅ Sekarang tetap return sesuatu
        }

        $this->exportModal = false;
        $this->success('Export dimulai...', position: 'toast-top');

        return Excel::download(new StokTelurExport($this->startDate, $this->endDate), 'stok-telur.xlsx');
    }

    public function delete($id): void
    {
        DB::transaction(function () use ($id) {
            $stok = Stok::findOrFail($id);
            $inv = substr($stok->invoice, -4);
            $tgl = explode('-', $stok->invoice)[1];

            /* =========================
         1️⃣ ROLLBACK FIFO STOK
        ========================== */
            // rollback stok masuk lama
            if ($stok->tambah > 0) {
                $this->fifo($stok->barang_id, $stok->tambah, 'out');
            }

            // rollback stok keluar lama
            if ($stok->kurang > 0) {
                $this->fifo($stok->barang_id, $stok->kurang, 'in');
            }

            if ($stok->kotor > 0) {
                $this->fifo($stok->barang_id, $stok->kotor, 'in');
            }

            if ($stok->kotor < 0) {
                $this->fifo($stok->barang_id, abs($stok->kotor), 'out');
            }

            if ($stok->rusak > 0) {
                $this->fifo($stok->barang_id, $stok->rusak, 'in');
            }

            if ($stok->ceplok > 0) {
                $this->fifo($stok->barang_id, $stok->ceplok, 'in');
            }

            if ($stok->bentes > 0) {
                $this->fifo($stok->barang_id, $stok->bentes, 'in');
            }

            if ($stok->jumbo > 0) {
                $this->fifo($stok->barang_id, $stok->jumbo, 'in');
            }

            /* =========================
         2️⃣ HAPUS TRANSAKSI TURUNAN
        ========================== */
            $transaksis = Transaksi::where('invoice', 'like', "INV-$tgl-%-$inv")->get();

            foreach ($transaksis as $trx) {
                $trx->details()->delete();
                $trx->delete();
            }

            /* =========================
         3️⃣ HAPUS STOK UTAMA
        ========================== */
            $stok->delete();
        });

        $this->warning('Stok berhasil dihapus & stok dikembalikan', position: 'toast-top');
    }

    private function kurangiStokFifoDanHitungHpp(int $barangId, float $qtyKeluar, int $detailId): float
    {
        $totalHpp = 0;

        $batches = StokBatch::where('barang_id', $barangId)->where('qty_sisa', '>', 0)->orderBy('tanggal')->lockForUpdate()->get();

        foreach ($batches as $batch) {
            if ($qtyKeluar <= 0) {
                break;
            }

            $ambil = min($batch->qty_sisa, $qtyKeluar);

            $batch->decrement('qty_sisa', $ambil);

            // ✅ SIMPAN FIFO KELUAR
            StokKeluarBatch::create([
                'detail_transaksi_id' => $detailId,
                'stok_batch_id' => $batch->id,
                'qty' => $ambil,
                'returned_qty' => 0,
                'harga' => $batch->harga,
            ]);

            $totalHpp += $ambil * $batch->harga;
            $qtyKeluar -= $ambil;
        }

        return $totalHpp;
    }

    public function openStatusModal($id): void
    {
        $this->selectedId = $id;
        $this->selectedInv = Transaksi::find($id)->invoice ?? null;
        $this->status = Transaksi::find($id)->status ?? 'Perbaikan';
        $this->statusModal = true;
    }

    public function updateStatus(): void
    {
        DB::transaction(function () {
            $stok = Stok::findOrFail($this->selectedId);
            $barang = Barang::findOrFail($stok->barang_id);
            if ($this->status == 'Selesai') {
                $stok->update(['status' => 'Selesai']);

                $str = substr($stok->invoice, -4);
                $part = explode('-', $stok->invoice);
                $tanggal = $part[1];

                $invoice = 'INV-' . $tanggal . '-STK-' . $str;
                $invoice1 = 'INV-' . $tanggal . '-KTR-' . $str;
                $invoice2 = 'INV-' . $tanggal . '-BTS-' . $str;
                $invoice3 = 'INV-' . $tanggal . '-CLK-' . $str;
                $invoice4 = 'INV-' . $tanggal . '-PRK-' . $str;
                $invoice5 = 'INV-' . $tanggal . '-JMB-' . $str;
                $invoice6 = 'INV-' . $tanggal . '-TLR1-' . $str;
                $invoice7 = 'INV-' . $tanggal . '-TLR2-' . $str;
                $invoice8 = 'INV-' . $tanggal . '-TLR3-' . $str;
                $invoice9 = 'INV-' . $tanggal . '-TLR4-' . $str;
                $invoice10 = 'INV-' . $tanggal . '-TLR5-' . $str;
                $invoice11 = 'INV-' . $tanggal . '-TBH-' . $str;
                $invoice12 = 'INV-' . $tanggal . '-TLR6-' . $str;
                $invoice13 = 'INV-' . $tanggal . '-KRG-' . $str;
                $invoice14 = 'INV-' . $tanggal . '-TLR7-' . $str;

                $kateKotor = Kategori::where('name', 'like', '%Telur Kotor%')->first();
                $kateProk = Kategori::where('name', 'like', '%Telur Prok%')->first();
                $kateBentes = Kategori::where('name', 'like', '%Telur Bentes%')->first();
                $kateCeplok = Kategori::where('name', 'like', '%Telur Ceplok%')->first();
                $kateTelur = Kategori::where('name', 'like', '%Stok Telur%')->first();
                $kateJumbo = Kategori::where('name', 'like', '%Telur Jumbo%')->first();
                $kateStok = Kategori::where('name', 'like', '%Penyesuaian Stok')->first();

                if ($stok->tambah > 0) {
                    $harga = StokBatch::where('barang_id', $stok->barang_id)->latest('tanggal')->value('harga') ?? 0;
                    $hppTambah = $harga * $stok->tambah;
                    $tambah = Transaksi::create([
                        'invoice' => $invoice11,
                        'name' => 'Telur Tambah ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Debit',
                        'total' => $hppTambah,
                    ]);

                    $detail = DetailTransaksi::create([
                        'transaksi_id' => $tambah->id,
                        'kategori_id' => $kateStok->id ?? null,
                        'value' => $harga,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->tambah,
                        'sub_total' => $harga * $stok->tambah,
                    ]);

                    // ✅ BUAT BATCH
                    StokBatch::create([
                        'barang_id' => $stok->barang_id,
                        'detail_transaksi_id' => $detail->id,
                        'user_id' => $stok->user_id,
                        'qty_masuk' => $stok->tambah,
                        'qty_sisa' => $stok->tambah,
                        'harga' => $harga,
                        'tanggal' => $stok->tanggal,
                    ]);

                    // Telur Kadaluarsa - Kredit
                    $telur2 = Transaksi::create([
                        'invoice' => $invoice12,
                        'name' => 'Telur Tambah ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Kredit',
                        'total' => $hppTambah,
                    ]);

                    DetailTransaksi::create([
                        'transaksi_id' => $telur2->id,
                        'kategori_id' => $kateTelur->id ?? null,
                        'value' => $hppTambah / $stok->tambah,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->tambah,
                        'sub_total' => $hppTambah,
                    ]);
                }

                if ($stok->kurang > 0) {
                    $kurang = Transaksi::create([
                        'invoice' => $invoice13,
                        'name' => 'Telur Kurang ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Kredit',
                        'total' => 0,
                    ]);

                    $detail = DetailTransaksi::create([
                        'transaksi_id' => $kurang->id,
                        'kategori_id' => $kateStok->id ?? null,
                        'value' => 0,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->kurang,
                        'sub_total' => 0,
                    ]);

                    $hppKurang = $this->kurangiStokFifoDanHitungHpp($stok->barang_id, $stok->kurang, $detail->id);

                    $detail->update([
                        'value' => $hppKurang / $stok->kurang,
                        'sub_total' => $hppKurang,
                    ]);

                    $kurang->update(['total' => $hppKurang]);

                    // Telur Kadaluarsa - Kredit
                    $telur2 = Transaksi::create([
                        'invoice' => $invoice14,
                        'name' => 'Telur Kurang ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Debit',
                        'total' => 0,
                    ]);

                    DetailTransaksi::create([
                        'transaksi_id' => $telur2->id,
                        'kategori_id' => $kateTelur->id ?? null,
                        'value' => $hppKurang / $stok->kurang,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->kurang,
                        'sub_total' => $hppKurang,
                    ]);
                }

                if ($stok->kotor > 0) {
                    // TELUR KOTOR - Debit
                    $kotor = Transaksi::create([
                        'invoice' => $invoice1,
                        'name' => 'Telur Kotor ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Debit',
                        'total' => 0,
                    ]);

                    $detail = DetailTransaksi::create([
                        'transaksi_id' => $kotor->id,
                        'kategori_id' => $kateKotor->id ?? null,
                        'value' => 0,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->kotor,
                        'sub_total' => 0,
                    ]);

                    $hppKotor = $this->kurangiStokFifoDanHitungHpp($stok->barang_id, $stok->kotor, $detail->id);

                    $detail->update([
                        'value' => $hppKotor / $stok->kotor,
                        'sub_total' => $hppKotor,
                    ]);

                    $kotor->update(['total' => $hppKotor]);

                    // TELUR KOTOR - Kredit
                    $telur1 = Transaksi::create([
                        'invoice' => $invoice6,
                        'name' => 'Telur Kotor ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Kredit',
                        'total' => $hppKotor,
                    ]);

                    DetailTransaksi::create([
                        'transaksi_id' => $telur1->id,
                        'kategori_id' => $kateTelur->id ?? null,
                        'value' => $hppKotor / $stok->kotor,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->kotor,
                        'sub_total' => $hppKotor,
                    ]);
                } elseif ($stok->kotor < 0) {
                    $qty = abs($stok->kotor);
                    $harga = StokBatch::where('barang_id', $stok->barang_id)->latest('tanggal')->value('harga') ?? 0;
                    $hppKotor = $harga * $qty;

                    // TELUR KOTOR - Debit
                    $kotor = Transaksi::create([
                        'invoice' => $invoice1,
                        'name' => 'Telur Kotor ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Kredit',
                        'total' => $hppKotor,
                    ]);

                    $detail = DetailTransaksi::create([
                        'transaksi_id' => $kotor->id,
                        'kategori_id' => $kateKotor->id ?? null,
                        'value' => $hppKotor / $qty,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $qty,
                        'sub_total' => $hppKotor,
                    ]);

                    // ✅ WAJIB: BUAT BATCH BARU
                    StokBatch::create([
                        'barang_id' => $stok->barang_id,
                        'detail_transaksi_id' => $detail->id,
                        'user_id' => $stok->user_id,
                        'qty_masuk' => $qty,
                        'qty_sisa' => $qty,
                        'harga' => $harga,
                        'tanggal' => $stok->tanggal,
                    ]);

                    // TELUR KOTOR - Kredit
                    $telur1 = Transaksi::create([
                        'invoice' => $invoice6,
                        'name' => 'Telur Kotor ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Debit',
                        'total' => $hppKotor,
                    ]);

                    DetailTransaksi::create([
                        'transaksi_id' => $telur1->id,
                        'kategori_id' => $kateTelur->id ?? null,
                        'value' => $hppKotor / $qty,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $qty,
                        'sub_total' => $hppKotor,
                    ]);
                }

                // TELUR BENTES - Debit
                if ($stok->bentes > 0) {
                    $bentes = Transaksi::create([
                        'invoice' => $invoice2,
                        'name' => 'Telur Bentes ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Debit',
                        'total' => 0,
                    ]);

                    $detail = DetailTransaksi::create([
                        'transaksi_id' => $bentes->id,
                        'kategori_id' => $kateBentes->id ?? null,
                        'value' => 0,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->bentes,
                        'sub_total' => 0,
                    ]);

                    $hppBentes = $this->kurangiStokFifoDanHitungHpp($stok->barang_id, $stok->bentes, $detail->id);

                    $detail->update([
                        'value' => $hppBentes / $stok->bentes,
                        'sub_total' => $hppBentes,
                    ]);

                    $bentes->update(['total' => $hppBentes]);

                    // TELUR KOTOR - Kredit
                    $telur1 = Transaksi::create([
                        'invoice' => $invoice7,
                        'name' => 'Telur Kotor ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Kredit',
                        'total' => $hppBentes,
                    ]);

                    DetailTransaksi::create([
                        'transaksi_id' => $telur1->id,
                        'kategori_id' => $kateTelur->id ?? null,
                        'value' => $hppBentes / $stok->bentes,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->bentes,
                        'sub_total' => $hppBentes,
                    ]);
                }

                // TELUR CEPLOK - Debit
                if ($stok->ceplok > 0) {
                    $ceplok = Transaksi::create([
                        'invoice' => $invoice3,
                        'name' => 'Telur Ceplok ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Debit',
                        'total' => 0,
                    ]);

                    $detail = DetailTransaksi::create([
                        'transaksi_id' => $ceplok->id,
                        'kategori_id' => $kateCeplok->id ?? null,
                        'value' => 0,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->ceplok,
                        'sub_total' => 0,
                    ]);

                    $hppCeplok = $this->kurangiStokFifoDanHitungHpp($stok->barang_id, $stok->ceplok, $detail->id);

                    $detail->update([
                        'value' => $hppCeplok / $stok->ceplok,
                        'sub_total' => $hppCeplok,
                    ]);

                    $ceplok->update(['total' => $hppCeplok]);

                    // TELUR KOTOR - Kredit
                    $telur1 = Transaksi::create([
                        'invoice' => $invoice8,
                        'name' => 'Telur Ceplok ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Kredit',
                        'total' => $hppCeplok,
                    ]);

                    DetailTransaksi::create([
                        'transaksi_id' => $telur1->id,
                        'kategori_id' => $kateTelur->id ?? null,
                        'value' => $hppCeplok / $stok->ceplok,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->ceplok,
                        'sub_total' => $hppCeplok,
                    ]);
                }

                // TELUR PROK - Debit
                if ($stok->rusak > 0) {
                    $prok = Transaksi::create([
                        'invoice' => $invoice4,
                        'name' => 'Telur Prok ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Debit',
                        'total' => 0,
                    ]);

                    $detail = DetailTransaksi::create([
                        'transaksi_id' => $prok->id,
                        'kategori_id' => $kateProk->id ?? null,
                        'value' => 0,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->prok,
                        'sub_total' => 0,
                    ]);

                    $hppProk = $this->kurangiStokFifoDanHitungHpp($stok->barang_id, $stok->rusak, $detail->id);

                    $detail->update([
                        'value' => $hppProk / $stok->rusak,
                        'sub_total' => $hppProk,
                    ]);

                    $prok->update(['total' => $hppProk]);

                    // TELUR KOTOR - Kredit
                    $telur1 = Transaksi::create([
                        'invoice' => $invoice9,
                        'name' => 'Telur Prok ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Kredit',
                        'total' => $hppProk,
                    ]);

                    DetailTransaksi::create([
                        'transaksi_id' => $telur1->id,
                        'kategori_id' => $kateTelur->id ?? null,
                        'value' => $hppProk / $stok->rusak,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->rusak,
                        'sub_total' => $hppProk,
                    ]);
                }

                // TELUR JUMBO - Debit
                if ($stok->jumbo > 0) {
                    // TELUR KOTOR - Debit
                    $jumbo = Transaksi::create([
                        'invoice' => $invoice5,
                        'name' => 'Telur Jumbo ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Debit',
                        'total' => 0,
                    ]);

                    DetailTransaksi::create([
                        'transaksi_id' => $jumbo->id,
                        'kategori_id' => $kateJumbo->id ?? null,
                        'value' => 0,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->jumbo,
                        'sub_total' => 0,
                    ]);

                    $hppJumbo = $this->kurangiStokFifoDanHitungHpp($stok->barang_id, $stok->jumbo, $detail->id);

                    $detail->update([
                        'value' => $hppJumbo / $stok->jumbo,
                        'sub_total' => $hppJumbo,
                    ]);

                    $jumbo->update(['total' => $hppJumbo]);

                    // TELUR KOTOR - Kredit
                    $telur1 = Transaksi::create([
                        'invoice' => $invoice10,
                        'name' => 'Telur Jumbo ' . Barang::find($stok->barang_id)->name,
                        'user_id' => $stok->user_id,
                        'tanggal' => $stok->tanggal,
                        'type' => 'Kredit',
                        'total' => $hppJumbo,
                    ]);

                    DetailTransaksi::create([
                        'transaksi_id' => $telur1->id,
                        'kategori_id' => $kateTelur->id ?? null,
                        'value' => $hppJumbo / $stok->jumbo,
                        'barang_id' => $stok->barang_id,
                        'kuantitas' => $stok->jumbo,
                        'sub_total' => $hppJumbo,
                    ]);
                }

                $this->success("Status stok {$stok->invoice} berhasil diubah menjadi Selesai", position: 'toast-top');
            } else {
                $stok->update(['status' => 'Batal']);
                $this->success("Status stok {$stok->invoice} berhasil diubah menjadi Batal", position: 'toast-top');
            }
        });

        $this->statusModal = false;
    }

    public function headers(): array
    {
        return [['key' => 'invoice', 'label' => 'Invoice', 'class' => 'w-36'], ['key' => 'barang.name', 'label' => 'Barang', 'class' => 'w-36'], ['key' => 'tanggal', 'label' => 'Tanggal', 'class' => 'w-16'], ['key' => 'user.name', 'label' => 'Pembuat', 'class' => 'w-16'], ['key' => 'tambah', 'label' => ' Tambah', 'class' => 'w-16'], ['key' => 'kurang', 'label' => ' Kurang', 'class' => 'w-16'], ['key' => 'kotor', 'label' => ' Kotor', 'class' => 'w-16'], ['key' => 'bentes', 'label' => ' Bentes', 'class' => 'w-16'], ['key' => 'ceplok', 'label' => ' Ceplok', 'class' => 'w-16'], ['key' => 'rusak', 'label' => ' Pecah', 'class' => 'w-16'], ['key' => 'jumbo', 'label' => ' Jumbo', 'class' => 'w-16'], ['key' => 'status', 'label' => 'Status', 'class' => 'w-16']];
    }

    public function transaksi(): LengthAwarePaginator
    {
        return Stok::query()
            ->with(['barang:id,name', 'user:id,name'])
            ->whereHas('barang.jenis', function ($q) {
                $q->where('name', 'like', 'Telur%');
            })
            ->when($this->search, function (Builder $query) {
                $query
                    ->whereHas('barang', function ($q) {
                        $q->where('name', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('user', function ($q) {
                        $q->where('name', 'like', "%{$this->search}%");
                    })
                    ->orWhere('invoice', 'like', "%{$this->search}%");
            })
            ->when($this->barang_id, fn(Builder $q) => $q->where('barang_id', $this->barang_id))
            ->when(
                !empty($this->sortBy),
                function (Builder $q) {
                    $sortBy = $this->sortBy;
                    $column = $sortBy['column'] ?? 'created_at';
                    $direction = $sortBy['direction'] ?? 'desc';
                    $q->orderBy($column, $direction);
                },
                fn(Builder $q) => $q->orderBy('created_at', 'desc'),
            )
            ->when($this->startDate, fn(Builder $q) => $q->whereDate('tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn(Builder $q) => $q->whereDate('tanggal', '<=', $this->endDate))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        if ($this->filter >= 0 && $this->filter < 3) {
            $this->filter = 0;
            if (!empty($this->search)) {
                $this->filter++;
            }
            if ($this->barang_id != 0) {
                $this->filter++;
            }
            if ($this->startDate != null) {
                $this->filter++;
            }
        }
        return [
            'transaksi' => $this->transaksi(),
            'barang' => Barang::whereHas('jenis', function ($q) {
                $q->where('name', 'like', 'Telur %');
            })->get(),
            'headers' => $this->headers(),
            'perPage' => $this->perPage,
            'pages' => $this->page,
        ];
    }

    public function updated($property): void
    {
        if (!is_array($property) && $property != '') {
            $this->resetPage();
        }
    }
};

?>

<div class="p-4 space-y-6">
    <x-header title="Transaksi Stok Telur" separator progress-indicator>
        <x-slot:actions>
            <div class="flex flex-row sm:flex-row gap-2">
                <x-button wire:click="openExportModal" icon="fas.download" primary>Export Excel</x-button>
                <x-button label="Create" link="/stok-telur/create" responsive icon="o-plus" class="btn-primary" />
            </div>
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end mb-4">
        <div class="md:col-span-1">
            <x-select label="Show entries" :options="$pages" wire:model.live="perPage" />
        </div>
        <div class="md:col-span-6">
            <x-input placeholder="Cari..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </div>
        <div class="md:col-span-1">
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel"
                badge="{{ $this->filter }}" badge-classes="badge-primary" />
        </div>
    </div>

    <!-- TABLE -->
    <x-card class="overflow-x-auto">
        <x-table :headers="$headers" :rows="$transaksi" :sort-by="$sortBy" with-pagination
            link="stok-telur/{id}/show?barang={barang.name}">
            @scope('cell-kategori.name', $transaksi)
                {{ $transaksi->kategori?->name ?? '-' }}
            @endscope
            @scope('cell_status', $transaksi)
                @if ($transaksi->status == 'Selesai')
                    <span class="badge badge-success">{{ $transaksi->status }}</span>
                @elseif ($transaksi->status == 'Perbaikan')
                    <span class="badge badge-warning">{{ $transaksi->status }}</span>
                @else
                    <span class="badge badge-error">{{ $transaksi->status }}</span>
                @endif
            @endscope
            @scope('actions', $transaksi)
                <div class="flex">
                    @if (Auth::user()->role_id == 1 ||
                            (Carbon::parse($transaksi->tanggal)->isSameDay($this->today) && $transaksi->user_id == Auth::user()->id))
                        <x-button icon="o-pencil"
                            link="/stok-telur/{{ $transaksi->id }}/edit?invoice={{ $transaksi->invoice }}"
                            class="btn-ghost btn-sm text-yellow-500" />
                    @endif
                    @if (Auth::user()->role_id == 1)
                        <x-button icon="o-trash" wire:click="delete({{ $transaksi->id }})"
                            wire:confirm="Yakin ingin menghapus transaksi {{ $transaksi->invoice }} ini?" spinner
                            class="btn-ghost btn-sm text-red-500" />
                    @endif
                     @if ($transaksi->status == 'Perbaikan')
                        <x-button icon="o-pencil-square" wire:click="openStatusModal({{ $transaksi->id }})" spinner
                            class="btn-ghost btn-sm text-purple-500" tooltip="Update Status" />
                    @endif
                </div>
            @endscope
        </x-table>
    </x-card>

    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button
        class="w-full sm:w-[90%] md:w-1/2 lg:w-1/3">
        <div class="grid gap-5">
            <x-input placeholder="Cari Invoice..." wire:model.live.debounce="search" clearable
                icon="o-magnifying-glass" />

            <x-choices-offline placeholder="Pilih Barang" wire:model.live="barang_id" :options="$barang" icon="o-flag"
                single searchable />

            <!-- ✅ Tambahkan Filter Tanggal -->
            <x-input label="Tanggal Awal" type="date" wire:model.live="startDate" />
            <x-input label="Tanggal Akhir" type="date" wire:model.live="endDate" />

        </div>

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer=false" />
        </x-slot:actions>
    </x-drawer>

    <!-- ✅ MODAL EXPORT -->
    <x-modal wire:model="exportModal" title="Export Data" separator>
        <div class="grid gap-4">
            <x-input label="Start Date" type="date" wire:model="startDate" />
            <x-input label="End Date" type="date" wire:model="endDate" />
        </div>
        <x-slot:actions>
            <x-button label="Batal" @click="$wire.exportModal=false" />
            <x-button label="Export" class="btn-primary" wire:click="export" spinner />
        </x-slot:actions>
    </x-modal>

    <!-- ✅ MODAL UBAH STATUS -->
    <x-modal wire:model="statusModal" title="Ubah Status Transaksi" separator>
        <div class="space-y-4">

            <x-input label="Invoice" value="{{ $selectedInv ?: '-' }}" readonly />
            <x-select label="Status Baru" placeholder="Pilih Status" wire:model="status" :options="[
                ['id' => 'Perbaikan', 'name' => 'Perbaikan'],
                ['id' => 'Selesai', 'name' => 'Selesai'],
                ['id' => 'Batal', 'name' => 'Batal'],
            ]" />
        </div>

        <x-slot:actions>
            <x-button label="Batal" @click="$wire.statusModal=false" />
            <x-button label="Simpan" class="btn-primary" wire:click="updateStatus" spinner />
        </x-slot:actions>
    </x-modal>
</div>
