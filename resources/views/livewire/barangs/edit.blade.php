<?php

use Livewire\Volt\Component;
use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\StokBatch;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;

new class extends Component {
    // We will use it later
    use Toast, WithFileUploads;

    // Component parameter
    public Barang $barang;

    #[Rule('required|string')]
    public string $name = '';

    #[Rule('nullable|numeric|min:0')]
    public float $hpp = 0.0;

    #[Rule('required|exists:jenis_barangs,id')]
    public ?int $jenis_id = null;

    public function with(): array
    {
        return [
            'jenisbarangs' => JenisBarang::all(),
        ];
    }

    public function mount(): void
    {
        $this->fill($this->barang);
    }

    public function save(): void
    {
        // Validate
        $data = $this->validate();

        // Update
        $this->barang->update($data);

        $stok = StokBatch::where('barang_id', $this->barang->id)->where('qty_sisa', '>', 0)->get();
        foreach ($stok as $s) {
            $s->update(['harga' => $this->hpp]);
        }

        // You can toast and redirect to any route
        $this->success('Barang updated with success.', redirectTo: '/barangs');
    }
};

?>

<div>
    {{-- <dd>{{$this->photo}}</dd> --}}
    <x-header title="Update {{ $barang->name }}" separator />

    <x-form wire:submit="save">
        {{--  Basic section  --}}
        <div class="lg:grid grid-cols-8 gap-4">
            <div class="col-span-2">
                <x-header title="Basic" subtitle="Basic info from Barang" size="text-2xl" />
            </div>

            <div class="col-span-6 grid gap-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="col-span-3">
                        <x-input label="Name" wire:model="name" />
                    </div>
                    <x-select label="Jenis Barang" wire:model="jenis_id" :options="$jenisbarangs"
                        placeholder="Pilih jenis barang" />
                    <div class="col-span-2">
                        <x-input label="Harga HPP" wire:model="hpp" prefix="Rp " money="IDR" />
                    </div>
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" link="/barangs" />
            {{-- The important thing here is `type="submit"` --}}
            {{-- The spinner property is nice! --}}
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>

    </x-form>
</div>
