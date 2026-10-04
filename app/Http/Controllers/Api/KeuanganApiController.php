<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\Request;

class KeuanganApiController extends Controller
{
    public function hutang(Request $request)
    {
        $limit = $request->query('limit', 50);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Transaksi::with(['client', 'user', 'details.kategori'])
            ->whereHas('details.kategori', function ($q) {
                $q->where('name', 'like', 'Hutang%');
            });

        if ($startDate) {
            $query->whereDate('tanggal', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('tanggal', '<=', $endDate);
        }

        $transaksi = $query->orderBy('tanggal', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Data Hutang berhasil diambil',
            'data' => $transaksi->items(),
            'pagination' => [
                'current_page' => $transaksi->currentPage(),
                'last_page' => $transaksi->lastPage(),
                'per_page' => $transaksi->perPage(),
                'total' => $transaksi->total(),
            ]
        ]);
    }

    public function piutang(Request $request)
    {
        $limit = $request->query('limit', 50);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Transaksi::with(['client', 'user', 'details.kategori'])
            ->whereHas('details.kategori', function ($q) {
                $q->where('name', 'like', 'Piutang%');
            });

        if ($startDate) {
            $query->whereDate('tanggal', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('tanggal', '<=', $endDate);
        }

        $transaksi = $query->orderBy('tanggal', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Data Piutang berhasil diambil',
            'data' => $transaksi->items(),
            'pagination' => [
                'current_page' => $transaksi->currentPage(),
                'last_page' => $transaksi->lastPage(),
                'per_page' => $transaksi->perPage(),
                'total' => $transaksi->total(),
            ]
        ]);
    }

    public function beban(Request $request)
    {
        $limit = $request->query('limit', 50);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Transaksi::with(['client', 'user', 'details.kategori'])
            ->whereHas('details.kategori.detailKategori', function ($q) {
                $q->where('type', 'Pengeluaran');
            });

        if ($startDate) {
            $query->whereDate('tanggal', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('tanggal', '<=', $endDate);
        }

        $transaksi = $query->orderBy('tanggal', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Data Beban berhasil diambil',
            'data' => $transaksi->items(),
            'pagination' => [
                'current_page' => $transaksi->currentPage(),
                'last_page' => $transaksi->lastPage(),
                'per_page' => $transaksi->perPage(),
                'total' => $transaksi->total(),
            ]
        ]);
    }

    public function pendapatanLainnya(Request $request)
    {
        $limit = $request->query('limit', 50);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Transaksi::with(['client', 'user', 'details.kategori'])
            ->whereHas('details.kategori', function ($q) {
                $q->where('name', 'not like', '%Telur%')
                  ->where('name', 'not like', '%Pakan%')
                  ->where('name', 'not like', '%Obat-Obatan%')
                  ->where('name', 'not like', '%EggTray%');
            })
            ->whereHas('details.kategori.detailKategori', function ($q) {
                $q->where('type', 'Pendapatan');
            });

        if ($startDate) {
            $query->whereDate('tanggal', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('tanggal', '<=', $endDate);
        }

        $transaksi = $query->orderBy('tanggal', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Data Pendapatan Lainnya berhasil diambil',
            'data' => $transaksi->items(),
            'pagination' => [
                'current_page' => $transaksi->currentPage(),
                'last_page' => $transaksi->lastPage(),
                'per_page' => $transaksi->perPage(),
                'total' => $transaksi->total(),
            ]
        ]);
    }
}
