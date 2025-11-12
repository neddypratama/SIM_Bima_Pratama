<?php

use Livewire\Volt\Component;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Kategori;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;

new class extends Component {
    use Toast;

    public Transaksi $beban; // transaksi utama
    public ?Transaksi $bayar; // transaksi linked

    #[Rule('required')]
    public string $invoice = '';

    #[Rule('required')]
    public string $name = '';

    #[Rule('required|integer|min:1')]
    public int $total = 0;

    #[Rule('required')]
    public ?int $user_id = null;

    #[Rule('required')]
    public ?string $type = null;

    #[Rule('required')]
    public ?int $bayar_id = null; // ID kategori metode pembayaran

    #[Rule('required')]
    public ?int $kategori_id = null;

    public ?string $tanggal = null;

    public array $details = [];

    public function with(): array
    {
        return [
            'users' => User::all(),
            'kategoris' => Kategori::where('type', 'like', '%Pengeluaran%')->where('name', 'not like', '%HPP%')->where('name', 'not like', '%Truk%')->get(),
            'optionType' => [['id' => 'Debit', 'name' => 'Pengeluaran'], ['id' => 'Kredit', 'name' => 'Kembalian']],
            'kateBayar' => Kategori::where('name', 'like', '%Kas Tunai%')->orWhere('name', 'like', 'Bank%')->get(),
        ];
    }

    public function mount(Transaksi $transaksi): void
    {
        // Ambil transaksi utama
        $this->beban = Transaksi::with('details.kategori')->findOrFail($transaksi->id);

        // Set data form
        $this->invoice = $this->beban->invoice;
        $this->invoice2 = $this->kas?->invoice ?? '';
        $this->name = $this->beban->name;
        $this->total = $this->beban->total;
        $this->user_id = $this->beban->user_id;
        $this->type = $this->beban->type;
        $this->tanggal = \Carbon\Carbon::parse($this->beban->tanggal)->format('Y-m-d\TH:i');

        $inv = substr($transaksi->invoice, -4);
        $tanggal = \Carbon\Carbon::parse($this->tanggal)->format('Ymd');

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

    public function save(): void
    {
        $this->validate();

        // Ambil kategori pembayaran
        $kategoriBayar = Kategori::find($this->bayar_id);
        $inv = substr($this->invoice, -4);
        $tanggal = \Carbon\Carbon::parse($this->tanggal)->format('Ymd');

        if ($this->type == 'Debit') {
            $tipe = 'Kredit';
        } else {
            $tipe = 'Debit';
        }

        if ($kategoriBayar->name == 'Kas Tunai') {
            $this->bayar->update([
                'invoice' => 'INV-' . $tanggal . '-TNI-' . $inv,
                'name' => $this->name,
                'user_id' => $this->user_id,
                'tanggal' => $this->tanggal,
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
        $this->beban->update([
            'name' => $this->name,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'total' => $this->total,
        ]);

        $this->beban->details()->delete();
        DetailTransaksi::create([
            'transaksi_id' => $this->beban->id,
            'kategori_id' => $this->kategori_id,
            'kuantitas' => null,
            'value' => null,
            'sub_total' => $this->total,
        ]);

        $this->success('Transaksi berhasil diperbarui!', redirectTo: '/beban');
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
                        <x-input label="Rincian" wire:model="name" placeholder="Contoh: Beban Transportasi" />
                        <x-choices-offline label="Kategori" wire:model="kategori_id" :options="$kategoris"
                            placeholder="Pilih Kategori" single clearable searchable />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <x-choices-offline label="Metode Pembayaran" wire:model="bayar_id" :options="$kateBayar"
                            placeholder="Pilih Metode" single clearable searchable />
                        <x-select label="Tipe Transaksi" wire:model="type" :options="$optionType" placeholder="Pilih Tipe" />
                        <x-input label="Total Pengeluaran" wire:model="total" prefix="Rp" money />
                    </div>
                </div>
            </div>
        </x-card>

        <x-slot:actions>
            <x-button spinner label="Cancel" link="/beban" />
            <x-button spinner label="Update" icon="o-check" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
