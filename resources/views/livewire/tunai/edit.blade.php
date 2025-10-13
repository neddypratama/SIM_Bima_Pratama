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

    #[Rule('nullable')]
    public ?int $client_id = null;

    public ?int $kategori_id = null;

    #[Rule('required')]
    public ?string $type = null;

    #[Rule('nullable|integer')]
    public ?int $linked_id = null;

    public ?string $tanggal = null;

    public array $details = [];
    public $barangs = [];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'clients' => Client::all()->groupBy('type')->mapWithKeys(fn($group, $type) => [$type => $group->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()->toArray()])->toArray(),
            'listTransaksi' => Transaksi::with(['client:id,name', 'kategori:id,name,type', 'linked.linkedTransaksi'])
                ->whereHas('kategori', function ($q) {
                    $q->where('name', 'not like', '%Kas%')->where('name', 'not like', '%Bank%');
                })
                ->get()
                ->filter(function ($t) {
                    $totalLinked = $t->linked->sum(fn($l) => $l->linkedTransaksi->total ?? 0);
                    return $t->linked->isEmpty() || ($t->total - $totalLinked) > 0;
                })
                ->groupBy(fn($t) => $t->kategori->type ?? 'Tanpa Kategori')
                ->mapWithKeys(
                    fn($group, $label) => [
                        $label => $group
                            ->map(
                                fn($t) => [
                                    'id' => $t->id,
                                    'name' => "{$t->invoice} | {$t->name} | Rp " . number_format($t->total - $t->linked->sum(fn($l) => $l->linkedTransaksi->total)) . ' | ' . ($t->client->name ?? 'Tanpa Client'),
                                    'total_linked' => $t->linked->sum(fn($l) => $l->linkedTransaksi->total ?? 0),
                                ],
                            )
                            ->values()
                            ->toArray(),
                    ],
                )
                ->toArray(),
            'optionType' => [['id' => 'Debit', 'name' => 'Kas Masuk'], ['id' => 'Kredit', 'name' => 'Kas Keluar']],
        ];
    }

    public function mount(Transaksi $transaksi): void
    {
        $this->transaksi = $transaksi;

        // Isi form dari data yang ada
        $this->invoice = $transaksi->invoice;
        $this->name = $transaksi->name;
        $this->user_id = $transaksi->user_id;
        $this->client_id = $transaksi->client_id;
        $this->kategori_id = $transaksi->kategori_id;
        $this->type = $transaksi->type;
        // 🔍 Cek apakah transaksi ini muncul sebagai transaksi_id atau linked_id
        $link = \App\Models\TransaksiLink::where('transaksi_id', $transaksi->id)->orWhere('linked_id', $transaksi->id)->first();

        if ($link) {
            // Jika transaksi ini ada di sisi transaksi_id, ambil linked_id
            if ($link->transaksi_id == $transaksi->id) {
                $this->linked_id = $link->linked_id;
            } else {
                // Jika transaksi ini ada di sisi linked_id, ambil transaksi_id
                $this->linked_id = $link->transaksi_id;
            }
        } else {
            $this->linked_id = null;
        }
        $this->total = $transaksi->total;
        $this->tanggal = Carbon::parse($transaksi->tanggal)->format('Y-m-d\TH:i:s');

        // Ambil detail transaksi
        $this->details = $transaksi->details
            ->map(
                fn($d) => [
                    'barang_id' => $d->barang_id,
                    'kuantitas' => $d->kuantitas,
                ],
            )
            ->toArray();
    }

    public function save(): void
    {
        $this->validate();

        $tunai = $this->transaksi;

        // Update transaksi utama
        $tunai->update([
            'invoice' => $this->invoice,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'kategori_id' => $this->kategori_id,
            'client_id' => $this->client_id,
            'type' => $this->type,
            'total' => $this->total,
        ]);

        // Hapus link lama
        $tunai->linked()->delete();

        // Buat link baru jika ada
        if ($this->linked_id) {
            TransaksiLink::create([
                'transaksi_id' => $tunai->id,
                'linked_id' => $this->linked_id,
            ]);

            TransaksiLink::create([
                'transaksi_id' => $this->linked_id,
                'linked_id' => $tunai->id,
            ]);
        }

        $this->success('Transaksi berhasil diperbarui!', redirectTo: '/tunai');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Update Transaksi {{ $this->invoice }}" separator progress-indicator />

    <x-form wire:submit="save">
        <!-- SECTION: Basic Info -->
        <x-card>
            <div class="lg:grid grid-cols-5 gap-4">
                <div class="col-span-2">
                    <x-header title="Basic Info" subtitle="Buat transaksi baru" size="text-2xl" />
                </div>
                <div class="col-span-3 grid gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <x-input label="Invoice" wire:model="invoice" readonly />
                        <x-input label="User" :value="auth()->user()->name" readonly />
                        <x-datetime label="Date + Time" wire:model="tanggal" icon="o-calendar" type="datetime-local" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <x-input label="Rincian Transaksi" wire:model="name"
                                placeholder="Contoh: Bayar Pembelian Telur Ayam Ras" />
                        </div>
                        <x-select label="Tipe Transaksi" wire:model="type" :options="$optionType" placeholder="Pilih Tipe" />
                    </div>
                </div>
            </div>
        </x-card>

        <!-- SECTION: Detail Items -->
        <x-card>
            <div class="lg:grid grid-cols-5 gap-4">
                <div class="col-span-2">
                    <x-header title="Detail Items" subtitle="Tambah detail transaksi" size="text-2xl" />
                </div>
                <div class="col-span-3 grid gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <x-select-group wire:model="linked_id" label="Relasi Transaksi" :options="$listTransaksi"
                                placeholder="Pilih Transaksi" />
                        </div>
                        <x-input label="Nominal Pembayaran" wire:model="total" prefix="Rp" money />
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/tunai" />
            <x-button spinner label="Update" icon="o-check" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
