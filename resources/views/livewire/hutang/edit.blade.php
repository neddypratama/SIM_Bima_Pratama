<?php

use Livewire\Volt\Component;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\TransaksiLink;
use App\Models\Kategori;
use App\Models\Client;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;

new class extends Component {
    use Toast;

    public Transaksi $hutang; // transaksi utama
    public ?Transaksi $bayar; // transaksi linked

    #[Rule('required')]
    public string $invoice = '';

    #[Rule('required')]
    public string $name = '';

    #[Rule('required|integer|min:0')]
    public int $total = 0;

    #[Rule('required')]
    public ?int $user_id = null;

    #[Rule('required')]
    public ?int $kategori_id = null;

    #[Rule('required')]
    public ?string $type = null;

    #[Rule('nullable')]
    public ?int $client_id = null;

    #[Rule('required')]
    public ?int $bayar_id = null; // ID kategori metode pembayaran

    public ?string $tanggal = null;

    public array $details = [];

    public function mount(Transaksi $transaksi): void
    {
        // Ambil transaksi utama
        $this->hutang = $transaksi;

        // Set data form
        $this->invoice = $this->hutang->invoice;
        $this->name = $this->hutang->name;
        $this->total = $this->hutang->total;
        $this->user_id = $this->hutang->user_id;
        $this->kategori_id = $this->hutang->kategori_id;
        $this->client_id = $this->hutang->client_id;
        $this->type = $this->hutang->type;
        $this->tanggal = \Carbon\Carbon::parse($this->hutang->tanggal)->format('Y-m-d\TH:i');

        $inv = substr($transaksi->invoice, -4);
        $part = explode('-', $transaksi->invoice);
        $tanggal = $part[1];

        // Cari transaksi pembayaran (Tunai / Transfer)
        $bayar = Transaksi::where('invoice', 'like', "%$tanggal-TNI-$inv")->first();

        if (!$bayar) {
            $bayar = Transaksi::where('invoice', 'like', "%$tanggal-TFR-$inv")->first();
        }

        // Set jika ditemukan
        if ($bayar) {
            $this->bayar = $bayar;

            $firstDetail = $bayar->details()->first();
            $this->bayar_id = $firstDetail ? $firstDetail->kategori_id : null;
        } else {
            // Jika tidak ditemukan, hindari error dan beri nilai default
            $this->bayar = null;
            $this->bayar_id = null;
        }

        foreach ($transaksi->details as $detail) {
            $this->details[] = [
                'kategori_id' => $detail->kategori_id,
                'sub_total' => $detail->sub_total,
            ];

            $this->kategori_id = $detail->kategori_id;
        }
    }

    public function with(): array
    {
        return [
            'users' => User::all(),
            'clients' => Client::all(),
            'kategoris' => Kategori::where('type', 'like', '%Liabilitas%')->where('name', 'like', '%Hutang%')->get(),
            'optionType' => [['id' => 'Kredit', 'name' => 'Hutang Bertambah'], ['id' => 'Debit', 'name' => 'Hutang Berkurang']],
            'kateBayar' => Kategori::where('name', 'like', '%Kas Tunai%')->orWhere('name', 'like', 'Bank%')->get(),
        ];
    }

    public function save(): void
    {
        $this->validate();

        $oldClient = Client::find($this->hutang->getOriginal('client_id'));
        $newClient = Client::find($this->client_id);

        if ($this->type == 'Kredit') {
            $tipe = 'Debit';
            // Jika client lama dan baru berbeda
            if ($oldClient && $newClient && $oldClient->id !== $newClient->id) {
                // Kembalikan titipan client lama
                $oldClient->decrement('titipan', $this->hutang->total);

                // Tambahkan titipan ke client baru
                $newClient->increment('titipan', $this->total);
            } elseif ($newClient) {
                // Jika client sama, hanya update selisih total
                $selisih = $this->total - $this->hutang->total;

                if ($selisih > 0) {
                    $newClient->increment('titipan', $selisih);
                } elseif ($selisih < 0) {
                    $newClient->decrement('titipan', abs($selisih));
                }
            }
        } else {
            $tipe = 'Kredit';
            // Jika client lama dan baru berbeda
            if ($oldClient && $newClient && $oldClient->id !== $newClient->id) {
                // Kembalikan titipan client lama
                $oldClient->increment('titipan', $this->hutang->total);

                // Tambahkan titipan ke client baru
                $newClient->decrement('titipan', $this->total);
            } elseif ($newClient) {
                // Jika client sama, hanya update selisih total
                $selisih = $this->total - $this->hutang->total;

                if ($selisih > 0) {
                    $newClient->decrement('titipan', $selisih);
                } elseif ($selisih < 0) {
                    $newClient->increment('titipan', abs($selisih));
                }
            }
            dd($selisih);
        }

        // Ambil kategori pembayaran
        $kategoriBayar = Kategori::find($this->bayar_id);
        $inv = substr($this->invoice, -4);
        $part = explode('-', $this->hutang->invoice);
        $tanggal = $part[1];

        if ($kategoriBayar->name == 'Kas Tunai') {
            $this->bayar->update([
                'invoice' => 'INV-' . $tanggal . '-TNI-' . $inv,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'client_id' => $this->client_id,
                'type' => $tipe,
                'total' => $this->total,
            ]);
            $this->bayar->details()->delete();
            DetailTransaksi::create([
                'transaksi_id' => $this->bayar->id,
                'kategori_id' => $this->bayar_id,
                'value' => null,
                'kuantitas' => null,
                'sub_total' => $this->total,
            ]);
        } else {
            $this->bayar->update([
                'invoice' => 'INV-' . $tanggal . '-TFR-' . $inv,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
                'client_id' => $this->client_id,
                'type' => $tipe,
                'total' => $this->total,
            ]);
            $this->bayar->details()->delete();
            DetailTransaksi::create([
                'transaksi_id' => $this->bayar->id,
                'kategori_id' => $this->bayar_id,
                'value' => null,
                'kuantitas' => null,
                'sub_total' => $this->total,
            ]);
        }

        // Update transaksi utama
        $this->hutang->update([
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'client_id' => $this->client_id,
            'type' => $this->type,
            'total' => $this->total,
        ]);

        $this->hutang->details()->delete();
        DetailTransaksi::create([
            'transaksi_id' => $this->hutang->id,
            'kategori_id' => $this->kategori_id,
            'kuantitas' => null,
            'value' => null,
            'sub_total' => $this->total,
        ]);

        $this->success('Transaksi berhasil diperbarui!', redirectTo: '/hutang');
    }
};
?>

<div class="p-4 space-y-6">
    <x-header title="Update Transaksi {{ $this->invoice }}" separator progress-indicator />

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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input label="Rincian" wire:model="name" placeholder="Contoh: Titipan Pak Agus" />
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
                                {{ $clients->name . ' | ' . $clients->type . ' | ' . 'Rp ' . number_format($clients->titipan + (int) $this->total, 0, ',', '.') }}
                            @endscope
                        </x-choices-offline>

                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-select label="Tipe Transaksi" wire:model.live="type" :options="$optionType"
                            placeholder="Pilih Tipe" />
                        <x-choices-offline label="Kategori" wire:model="kategori_id" :options="$kategoris"
                            placeholder="Pilih Kategori" single clearable searchable />
                    </div>
                </div>
            </div>
        </x-card>

        <!-- SECTION: Detail Items -->
        <x-card>
            <div class="lg:grid grid-cols-8 gap-4">
                <div class="col-span-2">
                    <x-header title="Detail Items" subtitle="Tambah detail transaksi" size="text-2xl" />
                </div>

                <div class="col-span-6 grid gap-3">
                    <div class="grid grid-cols-2 gap-4">
                        <x-choices-offline label="Metode Pembayaran" wire:model="bayar_id" :options="$kateBayar"
                            placeholder="Pilih Metode" single clearable searchable />
                        <x-input label="Total Pembayaran" wire:model="total" prefix="Rp" money />
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/hutang" />
            <x-button spinner label="Update" icon="o-check" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
