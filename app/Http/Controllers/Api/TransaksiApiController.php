<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\Request;

class TransaksiApiController extends Controller
{
    public function pembelian(Request $request)
    {
        $limit = $request->query('limit', 50);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Transaksi::with(['client', 'user', 'details.barang', 'details.kategori'])
            ->where('type', 'Debit')
            ->whereHas('details.kategori', function ($q) {
                $q->where('name', 'like', 'Pembelian%');
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
            'message' => 'Data Pembelian berhasil diambil',
            'data' => $transaksi->items(),
            'pagination' => [
                'current_page' => $transaksi->currentPage(),
                'last_page' => $transaksi->lastPage(),
                'per_page' => $transaksi->perPage(),
                'total' => $transaksi->total(),
            ]
        ]);
    }

    public function penjualan(Request $request)
    {
        $limit = $request->query('limit', 50);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Transaksi::with(['client', 'user', 'details.barang', 'details.kategori'])
            ->where('type', 'Kredit')
            ->whereHas('details.kategori', function ($q) {
                $q->where('name', 'like', 'Penjualan%');
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
            'message' => 'Data Penjualan berhasil diambil',
            'data' => $transaksi->items(),
            'pagination' => [
                'current_page' => $transaksi->currentPage(),
                'last_page' => $transaksi->lastPage(),
                'per_page' => $transaksi->perPage(),
                'total' => $transaksi->total(),
            ]
        ]);
    }

    public function retur(Request $request)
    {
        $limit = $request->query('limit', 50);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Transaksi::with(['client', 'user', 'details.barang', 'details.kategori'])
            ->whereHas('details.kategori', function ($q) {
                $q->where('name', 'like', 'Retur%');
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
            'message' => 'Data Retur berhasil diambil',
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
