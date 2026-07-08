<?php

namespace App\Livewire;

use App\Services\Reports\AssetReportService;
use Livewire\Volt\Component;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;
use App\Exports\AsetExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Builder;

new class extends Component {
    public $startDate;
    public $endDate;

    public $asetData = [];
    public $liabilitasData = [];
    public $expanded = [];

    public function mount()
    {
        $this->generateReport();
    }

    public function updated($field)
    {
        if (in_array($field, ['startDate', 'endDate'])) {
            $this->generateReport();
        }
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new AsetExport($this->endDate), 'aset.xlsx');
    }

    public function generateReport()
    {
        $report = app(AssetReportService::class)->generate($this->startDate, $this->endDate);

        $this->asetData = $report['asetData'];
        $this->liabilitasData = $report['liabilitasData'];
    }

    public function with()
    {
        $totalAset = array_sum(array_column($this->asetData, 'total'));
        $totalLiabilitas = array_sum(array_column($this->liabilitasData, 'total'));

        return [
            'asetData' => $this->asetData,
            'liabilitasData' => $this->liabilitasData,
            'totalAset' => $totalAset,
            'totalLiabilitas' => $totalLiabilitas,
            'totalModal' => $totalAset - $totalLiabilitas,
        ];
    }
};

?>

<div class="p-6 space-y-6">
    <x-header title="Laporan Aset" separator>
        <x-slot:actions>
            <x-button wire:click="export" icon="fas.download" primary>Export Excel</x-button>
            <div class="flex grid grid-cols-1 md:grid-cols-2 items-end">
                <x-input type="date" label="Dari Tanggal" wire:model.live="startDate" />
                <x-input type="date" label="Sampai Tanggal" wire:model.live="endDate" />
            </div>
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-card>
            <h3 class="text-lg font-semibold text-green-800">
                <i class="fas fa-coins text-green-600"></i> Total Aset
            </h3>
            <p class="text-2xl font-bold text-green-700 mt-2">Rp {{ number_format($totalAset, 0, ',', '.') }}</p>
        </x-card>

        <x-card>
            <h3 class="text-lg font-semibold text-red-800">
                <i class="fas fa-wallet text-red-600"></i> Total Liabilitas
            </h3>
            <p class="text-2xl font-bold text-red-700 mt-2">Rp {{ number_format($totalLiabilitas, 0, ',', '.') }}</p>
        </x-card>

        <x-card>
            <h3 class="text-lg font-semibold text-blue-800">
                <i class="fas fa-wallet text-blue-600"></i> Total Modal
            </h3>
            <p class="text-2xl font-bold text-blue-700 mt-2">Rp {{ number_format($totalModal, 0, ',', '.') }}</p>
        </x-card>
    </div>

    <x-card class="mt-4">
        <h3 class="text-xl font-semibold mb-4"><i class="fas fa-list-ul"></i> Rincian</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Aset -->
            <div>
                <h4 class="text-lg font-semibold text-green-700 mb-2">
                    <i class="fas fa-arrow-up"></i> Aset per Kelompok
                </h4>
                <ul class="divide-y divide-gray-200">
                    @foreach ($asetData as $kelompok => $data)
                        <li class="py-2">
                            <div class="flex justify-between cursor-pointer"
                                wire:click="$toggle('expanded.{{ $kelompok }}')">
                                <span class="font-medium">{{ $kelompok }}</span>
                                <span class="text-green-700">Rp {{ number_format($data['total'], 0, ',', '.') }}</span>
                            </div>
                            @if ($expanded[$kelompok] ?? false)
                                <ul class="pl-4 mt-2">
                                    @foreach ($data['detail'] as $sub => $val)
                                        <li class="flex justify-between py-1 text-green-600">
                                            <span>{{ $sub }}</span>
                                            <span>Rp {{ number_format($val, 0, ',', '.') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Liabilitas -->
            <div>
                <h4 class="text-lg font-semibold text-red-700 mb-2">
                    <i class="fas fa-arrow-down"></i> Liabilitas per Kelompok
                </h4>
                <ul class="divide-y divide-gray-200">
                    @foreach ($liabilitasData as $kelompok => $data)
                        <li class="py-2">
                            <div class="flex justify-between cursor-pointer"
                                wire:click="$toggle('expanded.{{ $kelompok }}')">
                                <span class="font-medium">{{ $kelompok }}</span>
                                <span class="text-red-700">Rp {{ number_format($data['total'], 0, ',', '.') }}</span>
                            </div>
                            @if ($expanded[$kelompok] ?? false)
                                <ul class="pl-4 mt-2">
                                    @foreach ($data['detail'] as $sub => $val)
                                        <li class="flex justify-between py-1 text-red-600">
                                            <span>{{ $sub }}</span>
                                            <span>Rp {{ number_format($val, 0, ',', '.') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>
    </x-card>
</div>
