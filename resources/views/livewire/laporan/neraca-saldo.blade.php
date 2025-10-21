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
    public array $neracaEkuitas = [];

    // Tambahkan property
    public array $imbalancedCategories = [];

    // Tambahkan di awal class Livewire
    public array $expanded = []; // <- untuk menyimpan state expand/collapse

    // Mapping kategori ke kelompok
    public array $mappingPendapatan = [
        'Pendapatan Telur' => ['Penjualan Telur Horn', 'Penjualan Telur Bebek', 'Penjualan Telur Puyuh', 'Penjualan Telur Arap'],
        'Pendapatan Pakan' => ['Penjualan Pakan Sentrat/Pabrikan', 'Penjualan Pakan Kucing', 'Penjualan Pakan Curah'],
        'Pendapatan Obat' => ['Penjualan Obat-Obatan'],
        'Pendapatan Eggtray' => ['Penjualan EggTray'],
        'Pendapatan Perlengkapan' => ['Penjualan Triplex', 'Penjualan Terpal', 'Penjualan Ban Bekas', 'Penjualan Sak Campur', 'Penjualan Tali'],
        'Pendapatan Non Penjualan' => ['Pemasukan Dapur', 'Pemasukan Transport Setoran', 'Pemasukan Transport Pedagang'],
        'Pendapatan Lain-Lain' => ['Penjualan Lain-Lain'],
    ];

    public array $mappingPengeluaran = [
        'Beban Transport' => ['Beban Transport', 'Beban BBM'],
        'Beban Operasional' => ['Beban Kantor', 'Beban Gaji', 'Beban Konsumsi', 'Peralatan', 'Perlengkapan', 'Beban Servis', 'Beban TAL'],
        'Beban Produksi' => ['Beban Telur Bentes', 'Beban Telur Ceplok', 'Beban Telur Prok', 'Beban Barang Kadaluarsa', 'HPP'],
        'Beban Bunga & Pajak' => ['Beban Bunga', 'Beban Pajak'],
        'Beban Sedekah' => ['ZIS'],
    ];

    public array $mappingAset = [
        'Piutang' => ['Piutang Peternak', 'Piutang Karyawan', 'Piutang Pedagang'],
        'Stok' => ['Stok Telur', 'Stok Pakan', 'Stok Obat-Obatan', 'Stok Tray', 'Stok Kotor', 'Stok Return'],
        'Kas' => ['Kas Tunai'],
        'Bank BCA' => ['Bank BCA Binti Wasilah', 'Bank BCA Masduki'],
        'Bank BNI' => ['Bank BNI Binti Wasilah', 'Bank BNI Bima Pratama'],
        'Bank BRI' => ['Bank BRI Binti Wasilah', 'Bank BRI Masduki'],
    ];

    public array $mappingLiabilitas = [
        'Hutang Pihak Lain' => ['Hutang Peternak', 'Hutang Karyawan', 'Hutang Pedagang', 'Hutang Bank'],
        'Hutang Supplier' => ['Saldo Bp.Supriyadi'],
        'Hutang Tray' => ['Hutang Tray Diamond /DM', 'Hutang Tray Super Buah /SB'],
        'Hutang Obat' => ['Hutang Obat SK', 'Hutang Obat Ponggok', 'Hutang Obat Random'],
        'Hutang Sentrat' => ['Hutang Sentrat SK', 'Hutang Sentrat Ponggok'],
    ];

    public array $mappingEkuitas = [
        'Modal' => ['Modal Awal'],
    ];

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

        $this->neracaPendapatan = [];
        $this->neracaPengeluaran = [];
        $this->neracaAset = [];
        $this->neracaLiabilitas = [];
        $this->neracaEkuitas = [];

        // Ambil transaksi dengan details & kategori
        $transaksis = Transaksi::with(['details.kategori'])
            ->whereBetween('tanggal', [$start, $end])
            ->whereHas('details', fn($q) => $q->where('sub_total', '>', 0))
            ->get();

        // Flatten semua detail
        $details = $transaksis
            ->flatMap(
                fn($trx) => $trx->details->map(
                    fn($d) => [
                        'kategori' => $d->kategori?->name,
                        'type_kategori' => $d->kategori?->type,
                        'type_transaksi' => strtolower($trx->type),
                        'sub_total' => $d->sub_total ?? 0,
                    ],
                ),
            )
            ->filter(fn($d) => $d['kategori']);

        // Ambil semua kategori
        $allKategoris = Kategori::select('name', 'type')->get();

        $complete = $allKategoris->map(
            fn($kategori) => [
                'kategori' => $kategori->name,
                'type' => $kategori->type,
                'debit' => $details->filter(fn($d) => $d['kategori'] === $kategori->name && $d['type_kategori'] === $kategori->type && $d['type_transaksi'] === 'debit')->sum('sub_total'),
                'kredit' => $details->filter(fn($d) => $d['kategori'] === $kategori->name && $d['type_kategori'] === $kategori->type && $d['type_transaksi'] === 'kredit')->sum('sub_total'),
            ],
        );

        $mapHierarki = function ($mapping, $type) use ($complete) {
            $result = [];
            foreach ($mapping as $group => $categories) {
                $sub = [];
                $totalDebit = 0;
                $totalKredit = 0;

                foreach ($categories as $cat) {
                    $row = $complete->first(fn($r) => $r['kategori'] === $cat && $r['type'] === $type);
                    if ($row) {
                        $sub[] = $row;
                        $totalDebit += $row['debit'];
                        $totalKredit += $row['kredit'];
                    }
                }

                $result[] = [
                    'group' => $group,
                    'debit' => $totalDebit,
                    'kredit' => $totalKredit,
                    'details' => $sub,
                ];
            }
            return $result;
        };

        $this->neracaPendapatan = $mapHierarki($this->mappingPendapatan, 'Pendapatan');
        $this->neracaPengeluaran = $mapHierarki($this->mappingPengeluaran, 'Pengeluaran');
        $this->neracaAset = $mapHierarki($this->mappingAset, 'Aset');
        $this->neracaLiabilitas = $mapHierarki($this->mappingLiabilitas, 'Liabilitas');
        $this->neracaEkuitas = $mapHierarki($this->mappingEkuitas, 'Ekuitas');
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
        $totalDebit = array_sum(array_column($this->neracaPendapatan, 'debit')) + array_sum(array_column($this->neracaPengeluaran, 'debit')) + array_sum(array_column($this->neracaAset, 'debit')) + array_sum(array_column($this->neracaLiabilitas, 'debit'));

        $totalKredit = array_sum(array_column($this->neracaPendapatan, 'kredit')) + array_sum(array_column($this->neracaPengeluaran, 'kredit')) + array_sum(array_column($this->neracaAset, 'kredit')) + array_sum(array_column($this->neracaLiabilitas, 'kredit'));

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
                            <tr class="cursor-pointer"
                                wire:click="$toggle('expanded.{{ $group['group'] }}')">
                            <tr class="cursor-pointer"
                                wire:click="$toggle('expanded.{{ $group['group'] }}')">
                                <td>
                                    <i class="fas fa-chevron-right mr-2"
                                        :class="{ 'fa-chevron-down': $expanded['{{ $group['group'] }}'] ?? false }"></i>
                                    {{ $group['group'] }}
                                </td>
                                <td class="text-center text-blue-600">
                                    {{ 'Rp ' . number_format($group['debit'], 0, ',', '.') }}
                                </td>
                                <td class="text-center text-green-600">
                                    {{ 'Rp ' . number_format($group['kredit'], 0, ',', '.') }}
                                </td>
                            </tr>

                            </tr>

                            @if ($expanded[$group['group']] ?? false)
                                @foreach ($group['details'] as $detail)
                                    <tr class="">
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
            @if ($totalDebit !== $totalKredit)
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
