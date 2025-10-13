<?php

namespace App\Livewire;

use App\Models\Transaksi;
use App\Models\Kategori;
use Livewire\Volt\Component;
use App\Exports\NeracaSaldoExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

new class extends Component {
    public string $startDate;
    public string $endDate;

    public array $neracaPendapatan = [];
    public array $neracaPengeluaran = [];
    public array $neracaAset = [];
    public array $neracaLiabilitas = [];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
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
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $kategoris = Kategori::all();

        $this->neracaPendapatan = [];
        $this->neracaPengeluaran = [];
        $this->neracaAset = [];
        $this->neracaLiabilitas = [];

        foreach ($kategoris as $kategori) {
            $transaksis = Transaksi::where('kategori_id', $kategori->id)
                ->whereBetween('tanggal', [$start, $end])
                ->get();

            $totalDebit = $transaksis->filter(fn($trx) => strtolower($trx->type) == 'debit')->sum('total');
            $totalKredit = $transaksis->filter(fn($trx) => strtolower($trx->type) == 'kredit')->sum('total');

            $row = [
                'kategori' => $kategori->name,
                'debit' => $totalDebit,
                'kredit' => $totalKredit,
            ];

            match ($kategori->type) {
                'Pendapatan' => ($this->neracaPendapatan[] = $row),
                'Pengeluaran' => ($this->neracaPengeluaran[] = $row),
                'Aset' => ($this->neracaAset[] = $row),
                'Liabilitas' => ($this->neracaLiabilitas[] = $row),
                default => null,
            };
        }
    }

    public function with()
    {
        $totalDebit = array_sum(array_column($this->neracaPendapatan, 'debit')) + array_sum(array_column($this->neracaPengeluaran, 'debit')) + array_sum(array_column($this->neracaAset, 'debit')) + array_sum(array_column($this->neracaLiabilitas, 'debit'));

        $totalKredit = array_sum(array_column($this->neracaPendapatan, 'kredit')) + array_sum(array_column($this->neracaPengeluaran, 'kredit')) + array_sum(array_column($this->neracaAset, 'kredit')) + array_sum(array_column($this->neracaLiabilitas, 'kredit'));

        return [
            'neracaPendapatan' => $this->neracaPendapatan,
            'neracaPengeluaran' => $this->neracaPengeluaran,
            'neracaAset' => $this->neracaAset,
            'neracaLiabilitas' => $this->neracaLiabilitas,
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
                    <tr>
                        <th class="text-left">Akun / Kategori</th>
                        <th class="text-center">Debit</th>
                        <th class="text-center">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Pendapatan -->
                    <tr class="font-bold">
                        <td colspan="3"><i class="fas fa-chart-line mr-2"></i>Pendapatan</td>
                    </tr>
                    @foreach ($neracaPendapatan as $row)
                        <tr>
                            <td>{{ $row['kategori'] }}</td>
                            <td class="text-center text-blue-600">
                                {{ $row['debit'] > 0 ? 'Rp ' . number_format($row['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-center text-green-600">
                                {{ $row['kredit'] > 0 ? 'Rp ' . number_format($row['kredit'], 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @endforeach

                    <!-- Pengeluaran -->
                    <tr class="font-bold">
                        <td colspan="3"><i class="fas fa-coins mr-2"></i>Pengeluaran</td>
                    </tr>
                    @foreach ($neracaPengeluaran as $row)
                        <tr>
                            <td>{{ $row['kategori'] }}</td>
                            <td class="text-center text-blue-600">
                                {{ $row['debit'] > 0 ? 'Rp ' . number_format($row['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-center text-green-600">
                                {{ $row['kredit'] > 0 ? 'Rp ' . number_format($row['kredit'], 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @endforeach

                    <!-- Aset -->
                    <tr class="font-bold">
                        <td colspan="3"><i class="fas fa-wallet mr-2"></i>Aset</td>
                    </tr>
                    @foreach ($neracaAset as $row)
                        <tr>
                            <td>{{ $row['kategori'] }}</td>
                            <td class="text-center text-blue-600">
                                {{ $row['debit'] > 0 ? 'Rp ' . number_format($row['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-center text-green-600">
                                {{ $row['kredit'] > 0 ? 'Rp ' . number_format($row['kredit'], 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @endforeach

                    <!-- Liabilitas -->
                    <tr class="font-bold">
                        <td colspan="3"><i class="fas fa-file-invoice-dollar mr-2"></i>Liabilitas</td>
                    </tr>
                    @foreach ($neracaLiabilitas as $row)
                        <tr>
                            <td>{{ $row['kategori'] }}</td>
                            <td class="text-center text-blue-600">
                                {{ $row['debit'] > 0 ? 'Rp ' . number_format($row['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-center text-green-600">
                                {{ $row['kredit'] > 0 ? 'Rp ' . number_format($row['kredit'], 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @endforeach

                    <!-- Total -->
                    <tr class="font-bold border-t-2">
                        <td>Total</td>
                        <td class="text-center text-blue-700">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
                        <td class="text-center text-green-700">Rp {{ number_format($totalKredit, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if ($totalDebit !== $totalKredit)
            <div class="mt-4 p-3 text-yellow-800 rounded bg-yellow-100">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Perhatian:</strong> Neraca tidak seimbang (Selisih:
                Rp {{ number_format($totalDebit - $totalKredit, 0, ',', '.') }})
            </div>
        @endif
    </x-card>
</div>
