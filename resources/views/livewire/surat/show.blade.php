<?php

use App\Models\Arsip;
use App\Models\Kategori;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use Toast;

    public Arsip $arsip;
    public ?string $pdfUrl = null;

    public function mount(Arsip $arsip): void
    {
        $this->arsip = $arsip;
    
        if ($arsip->file) {
            if (Storage::disk('public')->exists($arsip->file)) {
                $this->pdfUrl = Storage::url($arsip->file);
            } else {
                $this->error("File tidak ditemukan di path: {$arsip->file}", position: 'toast-bottom');
            }
        } else {
            $this->error('Tidak ada file terkait arsip ini.', position: 'toast-bottom');
        }
    }

    public function with(): array
    {
        return [
            'arsip' => $this->arsip,
            'kategori' => Kategori::all(),
        ];
    }
};
?>

<div class="p-5">
    <!-- HEADER -->
    <x-header title="Detail Surat" separator />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-5">
        <!-- Data Surat -->
        <div class="col-span-1 space-y-3">
            <x-card shadow>
                <p><strong>No Surat:</strong> {{ $arsip->no_surat }}</p>
                <p><strong>Judul:</strong> {{ $arsip->judul }}</p>
                <p><strong>Kategori:</strong> {{ $arsip->kategori->name ?? '-' }}</p>
                <p><strong>Tanggal:</strong> {{ $arsip->tanggal }}</p>
                
            </x-card>
        </div>

        <!-- PDF Preview -->
        <div class="col-span-1 lg:col-span-2">
            @if ($pdfUrl)
                <div class="w-full h-[90vh] border rounded-lg shadow">
                    <iframe src="{{ $pdfUrl }}" width="100%" height="100%" frameborder="0"></iframe>
                </div>
            @else
                <x-card shadow>
                    <p class="text-center text-red-500">File PDF tidak tersedia</p>
                </x-card>
            @endif
        </div>
    </div>
</div>
