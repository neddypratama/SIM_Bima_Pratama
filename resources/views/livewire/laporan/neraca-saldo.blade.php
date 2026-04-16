<?php

namespace App\Livewire;

use App\Models\Transaksi;
use App\Models\Kategori;
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
        $firstTransaction = Transaksi::orderBy('tanggal', 'asc')->first();
        $lastTransaction = Transaksi::orderBy('tanggal', 'desc')->first();

        $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : Carbon::parse($firstTransaction->tanggal)->startOfDay();

        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : Carbon::parse($lastTransaction->tanggal)->endOfDay();

        $this->neracaPendapatan = [];
        $this->neracaPengeluaran = [];
        $this->neracaAset = [];
        $this->neracaLiabilitas = [];
        $this->neracaEkuitas = [];

        $transaksis = Transaksi::with(['details.kategori.detailKategori', 'details.barang.jenis'])
            ->whereBetween('tanggal', [$start, $end])
            ->where('status', 'Selesai')
            ->whereHas('details', fn($q) => $q->where('sub_total', '>', 0))
            ->get();

        // 🔥 FLATTEN + SPLIT PENYESUAIAN
        $details = $transaksis
            ->flatMap(function ($trx) {
                return $trx->details->map(function ($d) use ($trx) {
                    $kategori = $d->kategori?->name;
                    $typeKategori = $d->kategori?->detailKategori?->type;
                    $jenis = strtolower($d->barang?->jenis?->name ?? '');

                    // 🔥 SPLIT PENYESUAIAN STOK → MASUK KE ASET
                    if ($kategori === 'Penyesuaian Stok') {
                        if (str_contains($jenis, 'telur')) {
                            $kategori = 'Stok Telur';
                            $typeKategori = 'Aset';
                        } elseif (str_contains($jenis, 'pakan')) {
                            $kategori = 'Stok Pakan';
                            $typeKategori = 'Aset';
                        } elseif (str_contains($jenis, 'obat')) {
                            $kategori = 'Stok Obat-Obatan';
                            $typeKategori = 'Aset';
                        } elseif (str_contains($jenis, 'tray')) {
                            $kategori = 'Stok Tray';
                            $typeKategori = 'Aset';
                        } else {
                            $kategori = null; // ❌ buang kalau tidak jelas
                        }
                    }

                    return [
                        'kategori' => $kategori,
                        'type_kategori' => $typeKategori,
                        'type_transaksi' => strtolower($trx->type),
                        'sub_total' => $d->sub_total ?? 0,
                    ];
                });
            })
            ->filter(fn($d) => $d['kategori']);

        // 🔥 AMBIL SEMUA KATEGORI DARI DB
        $allKategoris = Kategori::with('detailKategori')->get();

        $allKategoris = $allKategoris->map(function ($k) {
            return [
                'kategori' => $k->name,
                'type' => $k->detailKategori?->type,
            ];
        });

        // 🔥 TAMBAHKAN KATEGORI HASIL SPLIT
        $extraKategoris = collect([['kategori' => 'Stok Telur', 'type' => 'Aset'], ['kategori' => 'Stok Pakan', 'type' => 'Aset'], ['kategori' => 'Stok Obat-Obatan', 'type' => 'Aset'], ['kategori' => 'Stok Tray', 'type' => 'Aset']]);

        // 🔥 HAPUS PENYESUAIAN STOK + GABUNG
        $allKategoris = $allKategoris->reject(fn($k) => $k['kategori'] === 'Penyesuaian Stok')->merge($extraKategoris)->unique('kategori')->values();

        // 🔥 HITUNG DEBIT KREDIT
        $complete = collect($allKategoris)->map(function ($kategori) use ($details) {
            $nama = $kategori['kategori'];
            $type = $kategori['type'];

            return [
                'kategori' => $nama,
                'type' => $type,
                'debit' => $details->where('kategori', $nama)->where('type_kategori', $type)->where('type_transaksi', 'debit')->sum('sub_total'),
                'kredit' => $details->where('kategori', $nama)->where('type_kategori', $type)->where('type_transaksi', 'kredit')->sum('sub_total'),
            ];
        });

        // 🔥 MAPPING KE HIERARKI
        $mapHierarki = function ($mapping, $type) use ($complete) {
            $result = [];

            foreach ($mapping as $group => $categories) {
                $sub = [];
                $totalDebit = 0;
                $totalKredit = 0;

                foreach ($categories as $cat) {
                    $row = $complete->first(fn($r) => $r['kategori'] == $cat && $r['type'] == $type);

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
            @if ($totalDebit != $totalKredit && $totalDebit - $totalKredit == 0)
                {{-- @dd($totalDebit, $totalKredit) --}}
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
