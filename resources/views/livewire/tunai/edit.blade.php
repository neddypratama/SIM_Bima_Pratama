<?php

use Livewire\Volt\Component;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Kategori;
use App\Models\Client;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

new class extends Component {
    use Toast, WithFileUploads;

    public ?Transaksi $transaksi = null;

    #[Rule('required')]
    public string $invoice = '';
    public string $invoice2 = '';

    #[Rule('required')]
    public string $name = '';

    #[Rule('required|integer|min:0')]
    public int $total = 0;

    #[Rule('required')]
    public ?int $user_id = null;

    #[Rule('nullable')]
    public ?int $client_id = null;

    #[Rule('required')]
    public ?int $kategori_id = null;

    public ?string $tanggal = null;

    #[Rule('nullable|array|min:0')]
    public array $details = [];

    public array $clients = [];

    public ?Kategori $selectedKategori = null;

    #[Rule('nullable')]
    public string $alur = '';

    public $alurOption = [['id' => 'Masuk', 'name' => 'Masuk'], ['id' => 'Keluar', 'name' => 'Keluar']];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'kategoris' => Kategori::query()
                ->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('type', 'Aset')->where('name', 'not like', '%Stok%')->where('name', 'not like', '%Bank%');
                    })
                        ->orWhere('type', 'Liabilitas')
                        ->orWhere(function ($q3) {
                            $q3->where('type', 'Pengeluaran')->where('name', 'not like', '%Pembelian%');
                        });
                })
                ->orderBy('name')
                ->get(),
        ];
    }

    public function mount(Transaksi $transaksi): void
    {
        $this->transaksi = $transaksi;

        $kode = substr($this->transaksi->invoice, -4);
        $order = Transaksi::where('invoice', 'like', 'INV-%-BBN-' . $kode)->first();

        // isi semua properti dari transaksi
        $this->invoice = $this->transaksi->invoice;
        $this->invoice2 = $order->invoice;
        $this->name = $this->transaksi->name;
        $this->total = $this->transaksi->total;
        $this->user_id = $this->transaksi->user_id;
        $this->client_id = $this->transaksi->client_id;
        $this->kategori_id = $this->transaksi->kategori_id;
        $this->tanggal = \Carbon\Carbon::parse($this->transaksi->tanggal)->format('Y-m-d\TH:i');
        $this->details = $this->transaksi->details->toArray();

        $this->selectedKategori = Kategori::find($this->kategori_id);
        if (DetailTransaksi::where('transaksi_id', $this->transaksi->id)->first()->type=== 'Debit') {
            $this->alur = 'Masuk';
        } else {
            $this->alur = 'Keluar';
        }
        // dd($this->alur);
        

        $this->loadClients();
        if ($this->selectedKategori) {
            $this->updatedKategoriId($this->kategori_id);
        }
    }

    private function loadClients(?string $type = null): void
    {
        $query = Client::query();
        if ($type) {
            $query->where('type', $type);
        }

        $this->clients = $query
            ->get()
            ->groupBy('type')
            ->mapWithKeys(
                fn($group, $type) => [
                    $type => $group->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()->toArray(),
                ],
            )
            ->toArray();
    }

    public function updatedKategoriId($value): void
    {
        $this->selectedKategori = $value ? Kategori::find($value) : null;

        if ($this->selectedKategori) {
            if (Str::contains(strtolower($this->selectedKategori->name), 'peternak')) {
                $this->loadClients('peternak');
            } elseif (Str::contains(strtolower($this->selectedKategori->name), 'karyawan')) {
                $this->loadClients('karyawan');
            } elseif (Str::contains(strtolower($this->selectedKategori->name), 'pedagang')) {
                $this->loadClients('pedagang');
            } else {
                $this->loadClients();
            }
        } else {
            $this->loadClients();
        }
    }

    public function update(): void
    {
        $this->validate();

        $kategori = $this->selectedKategori;
        $total = $this->total;
        $client = $this->client_id;

        // === HAPUS DETAIL LAMA DULU ===
        DetailTransaksi::where('transaksi_id', $this->transaksi->id)->delete();

        // === 1. JIKA TYPE PENGELUARAN ===
        if ($kategori?->type === 'Pengeluaran') {
            $kasKategori = Kategori::where('name', 'like', '%Kas%')->first();
            // dd($kasKategori->id );   

            // Update transaksi KAS
            $this->transaksi->update([
                'invoice' => $this->invoice,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'total' => $total,
                'client_id' => null,
                'kategori_id' => $kasKategori->id,
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $this->transaksi->id,
                'type' => 'Kredit',
                'value' => $total,
            ]);

            // Update / buat ulang transaksi aset
            $aset = Transaksi::where('invoice', $this->invoice2)->first();
            $aset->update(
                ['invoice' => $this->invoice2],
                [
                    'name' => $this->name,
                    'user_id' => $this->user_id,
                    'tanggal' => $this->tanggal,
                    'total' => $total,
                    'client_id' => null,
                    'kategori_id' => $this->kategori_id,
                ],
            );

            DetailTransaksi::create([
                'transaksi_id' => $aset->id,
                'type' => 'Kredit',
                'value' => $total,
            ]);
        }
        // === 2. KAS TUNAI ===
        elseif ($kategori?->name === 'Kas Tunai') {
            $type = $this->alur === 'Masuk' ? 'Debit' : 'Kredit';

            $this->transaksi->update([
                'invoice' => $this->invoice,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'total' => $total,
                'client_id' => $client,
                'kategori_id' => $this->kategori_id,
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $this->transaksi->id,
                'type' => $type,
                'value' => $total,
            ]);
        }
        // === 3. BON ===
        elseif ($kategori?->name === 'Bon') {
            $this->transaksi->update([
                'invoice' => $this->invoice,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'total' => $total,
                'client_id' => $client,
                'kategori_id' => $this->kategori_id,
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $this->transaksi->id,
                'type' => 'Debit',
                'value' => $total,
            ]);
        }
        // === 4. HUTANG ===
        elseif ($kategori?->name === 'Hutang') {
            $this->transaksi->update([
                'invoice' => $this->invoice,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'total' => $total,
                'client_id' => $client,
                'kategori_id' => $this->kategori_id,
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $this->transaksi->id,
                'type' => 'Kredit',
                'value' => $total,
            ]);
        }

        $this->success('Transaksi berhasil diupdate!', redirectTo: '/tunai');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Edit Transaksi" separator progress-indicator />

    <x-form wire:submit="update">
        <div class="lg:grid grid-cols-5 gap-4">
            <div class="col-span-2">
                <x-header title="Basic Info" subtitle="Edit transaksi" size="text-2xl" />
            </div>
            <div class="col-span-3 grid gap-3">
                <div class="grid grid-cols-3 gap-4">
                    <x-input label="Invoice" wire:model="invoice" readonly />
                    <x-input label="User" :value="auth()->user()->name" readonly />
                    <x-datetime label="Date + Time" wire:model="tanggal" icon="o-calendar" type="datetime-local" />
                </div>
            </div>
        </div>

        <hr class="my-5" />

        <div class="lg:grid grid-cols-5 gap-4">
            <div class="col-span-2">
                <x-header title="Detail Items" subtitle="Edit barang dan total transaksi" size="text-2xl" />
            </div>
            <div class="col-span-3 grid gap-3">
                <x-input label="Rincian" wire:model="name" />

                <div class="grid grid-cols-2 gap-4">
                    <x-select wire:model.live="kategori_id" label="Kategori" :options="$kategoris"
                        placeholder="Pilih Kategori" />
                    <x-input label="Total" wire:model="total" prefix="Rp" locale="IDR" money />
                </div>

                @if ($selectedKategori)
                    @if (Str::contains(strtolower($selectedKategori->name), 'kas tunai'))
                        <div class="grid grid-cols-2 gap-4">
                            <x-select-group wire:model="client_id" label="Client" :options="$clients"
                                placeholder="Pilih Client" />
                            <x-select wire:model.live="alur" label="Jenis Transaksi" :options="$alurOption" />
                        </div>
                    @elseif(Str::contains(strtolower($selectedKategori->name), 'peternak') ||
                            Str::contains(strtolower($selectedKategori->name), 'karyawan') ||
                            Str::contains(strtolower($selectedKategori->name), 'pedagang'))
                        <x-select-group wire:model="client_id" label="Client" :options="$clients"
                            placeholder="Pilih Client" />
                    @endif
                @endif
            </div>
        </div>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/tunai" />
            <x-button spinner label="Update" icon="o-check" spinner="update" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
<?php
