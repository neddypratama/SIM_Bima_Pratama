<?php

namespace App\Livewire;

use App\Services\Reports\NeracaReportService;
use Livewire\Volt\Component;
use App\Exports\NeracaSaldoExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $startDate;
    public $endDate;

    public array $neracaPendapatan = [];
    public array $neracaPengeluaran = [];
    public array $neracaAset = [];
    public array $neracaLiabilitas = [];
    public array $neracaEkuitas = [];

    // Tambahkan property
    public array $imbalancedCategories = [];

    // Tambahkan di awal class Livewire
    public array $expanded = []; // <- untuk menyimpan state expand/collapse

    // Mapping kategori ke kelompok
    public array $mappingPendapatan = [];

    public array $mappingPengeluaran = [];

    public array $mappingAset = [];

    public array $mappingLiabilitas = [];

    public array $mappingEkuitas = [];

    public function mount()
    {
        $this->startDate = null;
        $this->endDate = null;

        // Ambil data laporan
        $laporans = DB::table('detail_kategoris as dk')
            ->leftJoin('kategoris as k', 'k.detail_kategori_id', '=', 'dk.id')
            ->select('dk.name as laporan', 'dk.type', 'k.name as kategori')
            ->whereNotNull('k.name') // Pastikan kategori tidak kosong
            ->orderBy('dk.id')
            ->get();

        // Reset mapping untuk memastikan data bersih
        $this->mappingPendapatan = [];
        $this->mappingPengeluaran = [];
        $this->mappingAset = [];
        $this->mappingLiabilitas = [];
        $this->mappingEkuitas = [];

        foreach ($laporans as $item) {
            // Tentukan array target berdasarkan 'type' dari detail_kategori
            $target = match ($item->type) {
                'Pendapatan' => 'mappingPendapatan',
                'Pengeluaran' => 'mappingPengeluaran',
                'Aset' => 'mappingAset',
                'Liabilitas' => 'mappingLiabilitas',
                'Ekuitas' => 'mappingEkuitas',
                default => null,
            };

            if ($target) {
                // Masukkan kategori ke dalam grup laporannya
                // Hasilnya: $this->mappingAset['Piutang Tray'][] = 'Piutang Tray Random'
                $this->{$target}[$item->laporan][] = $item->kategori;
            }
        }

        $this->generateNeraca();
    }

    public function updated($field)
    {
        if (in_array($field, ['startDate', 'endDate'])) {
            $this->generateNeraca();
        }
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new NeracaSaldoExport($this->startDate, $this->endDate), 'neraca_saldo.xlsx');
    }

    public function generateNeraca()
    {
        $report = app(NeracaReportService::class)->generate($this->startDate, $this->endDate, $this->mappingPendapatan, $this->mappingPengeluaran, $this->mappingAset, $this->mappingLiabilitas, $this->mappingEkuitas);

        $this->neracaPendapatan = $report['pendapatan'];
        $this->neracaPengeluaran = $report['pengeluaran'];
        $this->neracaAset = $report['aset'];
        $this->neracaLiabilitas = $report['liabilitas'];
        $this->neracaEkuitas = $report['ekuitas'];
    }

    // Tambahkan ini di dalam class Livewire kamu
    protected function getTotal(array $groupData): array
    {
        $debit = array_sum(array_column($groupData, 'debit'));
        $kredit = array_sum(array_column($groupData, 'kredit'));
        return ['debit' => $debit, 'kredit' => $kredit];
    }

    public function with()
    {
        $totalDebit = array_sum(array_column($this->neracaPendapatan, 'debit')) + array_sum(array_column($this->neracaPengeluaran, 'debit')) + array_sum(array_column($this->neracaAset, 'debit')) + array_sum(array_column($this->neracaLiabilitas, 'debit')) + array_sum(array_column($this->neracaEkuitas, 'debit'));

        $totalKredit = array_sum(array_column($this->neracaPendapatan, 'kredit')) + array_sum(array_column($this->neracaPengeluaran, 'kredit')) + array_sum(array_column($this->neracaAset, 'kredit')) + array_sum(array_column($this->neracaLiabilitas, 'kredit')) + array_sum(array_column($this->neracaEkuitas, 'kredit'));

        return [
            'neracaPendapatan' => $this->neracaPendapatan,
            'neracaPengeluaran' => $this->neracaPengeluaran,
            'neracaAset' => $this->neracaAset,
            'neracaLiabilitas' => $this->neracaLiabilitas,
            'neracaEkuitas' => $this->neracaEkuitas,
            'totalDebit' => $totalDebit,
            'totalKredit' => $totalKredit,
        ];
    }
};
?>

<div class="p-6 space-y-6">
    <x-header title="Neraca Saldo" separator>
        <x-slot:actions>
            <x-button wire:click="export" icon="fas.download" primary>Export Excel</x-button>
            <div class="flex grid grid-cols-1 md:grid-cols-2 items-end">
                <x-input type="date" label="Dari Tanggal" wire:model.live="startDate" />
                <x-input type="date" label="Sampai Tanggal" wire:model.live="endDate" />
            </div>
        </x-slot:actions>
    </x-header>

    <x-card>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr class="">
                        <th class="text-left px-4 py-2">Akun / Kategori</th>
                        <th class="text-center px-4 py-2">Debit</th>
                        <th class="text-center px-4 py-2">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (['Pendapatan' => $neracaPendapatan, 'Pengeluaran' => $neracaPengeluaran, 'Aset' => $neracaAset, 'Liabilitas' => $neracaLiabilitas, 'Ekuitas' => $neracaEkuitas] as $typeName => $groupData)
                        <tr class="font-bold ">
                            <td colspan="3">{{ $typeName }}</td>
                        </tr>

                        @foreach ($groupData as $group)
                            <tr class="cursor-pointer" wire:click="$toggle('expanded.{{ $group['group'] }}')">
                            <tr class="cursor-pointer" wire:click="$toggle('expanded.{{ $group['group'] }}')">
                                <td>
                                    <i
                                        class="fas mr-2 {{ $expanded[$group['group']] ?? false ? 'fa-chevron-down' : 'fa-chevron-right' }}"></i>
                                    {{ $group['group'] }}
                                </td>
                                <td class="text-center text-blue-600">
                                    Rp {{ number_format($group['debit'], 0, ',', '.') }}
                                </td>
                                <td class="text-center text-green-600">
                                    Rp {{ number_format($group['kredit'], 0, ',', '.') }}
                                </td>
                            </tr>

                            </tr>

                            @if ($expanded[$group['group']] ?? false)
                                @foreach ($group['details'] as $detail)
                                    <tr class="ml-3">
                                        <td class="pl-6">{{ $detail['kategori'] }}</td>
                                        <td class="text-center text-blue-600">
                                            {{ 'Rp ' . number_format($detail['debit'], 0, ',', '.') }}</td>
                                        <td class="text-center text-green-600">
                                            {{ 'Rp ' . number_format($detail['kredit'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach

                        <tr class="font-bold">
                            <td>Total {{ $typeName }}</td>
                            @php $total = $this->getTotal($groupData); @endphp
                            <td class="text-center">{{ 'Rp ' . number_format($total['debit'], 0, ',', '.') }}</td>
                            <td class="text-center">{{ 'Rp ' . number_format($total['kredit'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <!-- Total Keseluruhan -->
                    <tr class="font-bold border-t-2 =">
                        <td>Total Keseluruhan</td>
                        <td class="text-center text-blue-700">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
                        <td class="text-center text-green-700">Rp {{ number_format($totalKredit, 0, ',', '.') }}</td>
                    </tr>

                </tbody>
            </table>
            @if ($totalDebit != $totalKredit && Auth()->user()->role_id == 8)
                <div class="mt-4 p-3 text-yellow-800 rounded bg-yellow-100 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3"></i>
                    <span>
                        <strong>Perhatian:</strong> Neraca tidak seimbang
                        (Selisih: Rp {{ number_format(abs($totalDebit - $totalKredit), 0, ',', '.') }})
                    </span>
                </div>
            @endif

        </div>
    </x-card>
</div>
