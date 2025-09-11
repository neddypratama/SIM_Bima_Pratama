<?php

use Livewire\Volt\Component;
use App\Models\Kategori;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;
use Livewire\WithFileUploads;

new class extends Component {
    // We will use it later
    use Toast, WithFileUploads;

    // Component parameter
    public Kategori $kategori;

    #[Rule('required')]
    public string $name = '';

    #[Rule('sometimes')]
    public ?string $keterangan = null;

    public function mount(): void
    {
        $this->fill($this->kategori);
    }

    public function save(): void
    {
        // Validate
        $data = $this->validate();

        // Update
        $this->kategori->update($data);

        // You can toast and redirect to any route
        $this->success('Kategori updated with success.', redirectTo: '/kategori');
    }

    public function with(): array
    {
        return [
        ];
    }
};
?>

<div>
    <x-header title="Update {{ $kategori->name }}" separator />

    <x-form wire:submit="save">
        {{--  Basic section  --}}
        <div class="lg:grid grid-cols-5">
            <div class="col-span-2">
                <x-header title="Basic" subtitle="Basic info from kategori" size="text-2xl" />
            </div>
            <div class="col-span-3 grid gap-3">
                <x-input label="Name" wire:model="name" />
            </div>
        </div>

        {{--  Details section --}}
        <hr class="my-5" />

        <div class="lg:grid grid-cols-5">
            <div class="col-span-2">
                <x-header title="Details" subtitle="More about the kategori" size="text-2xl" />
            </div>
            <div class="col-span-3 grid gap-3">
                <x-textarea wire:model="keterangan" label="Keterangan" hint="Penjelasan mengenai nama kategori" />
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" link="/kategori" />
            {{-- The important thing here is `type="submit"` --}}
            {{-- The spinner property is nice! --}}
            <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
w