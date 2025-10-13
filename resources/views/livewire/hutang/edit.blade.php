<?php

use Livewire\Volt\Component;
use App\Models\Transaksi;
use App\Models\TransaksiLink;
use App\Models\Kategori;
use App\Models\Client;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;

new class extends Component {
    use Toast;

    public Transaksi $hutang; // transaksi utama
    public Transaksi $kas; // transaksi linked

    #[Rule('required')]
    public string $invoice = '';

    #[Rule('required')]
    public string $name = '';

    #[Rule('required|integer|min:1')]
    public int $total = 0;

    #[Rule('required')]
    public ?int $user_id = null;

    #[Rule('required')]
    public ?int $kategori_id = null;

    #[Rule('required')]
    public ?string $type = null;

    #[Rule('nullable')]
    public ?int $client_id = null;

    #[Rule('nullable|integer')]
    public ?int $linked_id = null;

    public ?string $tanggal = null;

    public function mount(Transaksi $transaksi): void
    {
        // Ambil transaksi utama
        $this->hutang = Transaksi::with('kategori')->findOrFail($transaksi->id);

        // Set data form
        $this->invoice = $this->hutang->invoice;
        $this->name = $this->hutang->name;
        $this->total = $this->hutang->total;
        $this->user_id = $this->hutang->user_id;
        $this->kategori_id = $this->hutang->kategori_id;
        $this->client_id = $this->hutang->client_id;
        $this->type = $this->hutang->type;
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
        $this->tanggal = \Carbon\Carbon::parse($this->hutang->tanggal)->format('Y-m-d\TH:i');
    }

    public function with(): array
    {
        return [
            'users' => User::all(),
            'clients' => Client::all()->groupBy('type')->mapWithKeys(fn($group, $type) => [$type => $group->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()->toArray()])->toArray(),
            'kategoris' => Kategori::where('type', 'like', '%Liabilitas%')->where('name', 'like', '%Hutang%')->get(),
            'optionType' => [['id' => 'Kredit', 'name' => 'Hutang Bertambah'], ['id' => 'Debit', 'name' => 'Hutang Berkurang']],
            'transaksiOptions' => Transaksi::with(['client:id,name', 'kategori:id,name,type', 'linked.linkedTransaksi'])
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
        ];
    }

    public function save(): void
    {
        $this->validate();

        $stok = $this->hutang;

        // Update transaksi utama
        $stok->update([
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
        $stok->linked()->delete();

        // Buat link baru jika ada
        if ($this->linked_id) {
            TransaksiLink::create([
                'transaksi_id' => $stok->id,
                'linked_id' => $this->linked_id,
            ]);

            TransaksiLink::create([
                'transaksi_id' => $this->linked_id,
                'linked_id' => $stok->id,
            ]);
        }

        $this->success('Transaksi berhasil diperbarui!', redirectTo: '/hutang');
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
                    <x-input label="Rincian" wire:model="name" />
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <x-select label="Tipe Transaksi" wire:model.live="type" :options="$optionType"
                            placeholder="Pilih Tipe" />
                        <x-select-group wire:model="client_id" label="Client" :options="$clients"
                            placeholder="Pilih Client" />
                        <x-select wire:model="kategori_id" label="Kategori" :options="$kategoris"
                            placeholder="Pilih Kategori" />
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
                    @if ($type == 'Kredit')
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <x-select-group wire:model="linked_id" label="Relasi Transaksi" :options="$transaksiOptions"
                                    placeholder="Pilih Transaksi" />
                            </div>

                            <x-input label="Total" wire:model="total" prefix="Rp" money />
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-4">

                            <x-input label="Total Pembayaran" wire:model="total" prefix="Rp" money />
                        </div>
                    @endif
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/hutang" />
            <x-button spinner label="Update" icon="o-check" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
