<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\Request;

class TransaksiApiController extends Controller
{
    /**
     * Data Pembelian (telur-masuk, sentrat-masuk, obat-masuk, tray-masuk)
     */
    public function pembelian(Request $request)
    {
        $limit = $request->query('limit', 500);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $category = strtolower($request->query('category', ''));

        $query = Transaksi::with(['client', 'user', 'details.barang', 'details.kategori'])
            ->where('type', 'Debit')
            ->whereHas('details.kategori', function ($q) use ($category) {
                if ($category === 'telur') {
                    $q->where('name', 'like', '%Stok Telur%');
                } elseif ($category === 'sentrat' || $category === 'pakan') {
                    $q->where('name', 'like', '%Stok Pakan%');
                } elseif ($category === 'obat') {
                    $q->where('name', 'like', '%Stok Obat%');
                } elseif ($category === 'tray') {
                    $q->where('name', 'like', '%Stok Tray%');
                } else {
                    $q->where(function ($sq) {
                        $sq->where('name', 'like', '%Stok Telur%')
                          ->orWhere('name', 'like', '%Stok Pakan%')
                          ->orWhere('name', 'like', '%Stok Obat%')
                          ->orWhere('name', 'like', '%Stok Tray%')
                          ->orWhere('name', 'like', 'Pembelian%');
                    });
                }
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
            'message' => 'Data Pembelian (masuk) berhasil diambil',
            'data' => $transaksi->items(),
            'pagination' => [
                'current_page' => $transaksi->currentPage(),
                'last_page' => $transaksi->lastPage(),
                'per_page' => $transaksi->perPage(),
                'total' => $transaksi->total(),
            ]
        ]);
    }

    /**
     * Data Penjualan (telur-keluar, sentrat-keluar, obat-keluar, tray-keluar)
     */
    public function penjualan(Request $request)
    {
        $limit = $request->query('limit', 500);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $category = strtolower($request->query('category', ''));

        $query = Transaksi::with(['client', 'user', 'details.barang', 'details.kategori'])
            ->where('type', 'Kredit')
            ->whereHas('details.kategori', function ($q) use ($category) {
                if ($category === 'telur') {
                    $q->where('name', 'like', 'Penjualan Telur%');
                } elseif ($category === 'sentrat' || $category === 'pakan') {
                    $q->where('name', 'like', 'Penjualan Pakan%');
                } elseif ($category === 'obat') {
                    $q->where('name', 'like', 'Penjualan Obat%');
                } elseif ($category === 'tray') {
                    $q->where(function ($sq) {
                        $sq->where('name', 'like', 'Penjualan Eggtray%')
                          ->orWhere('name', 'like', 'Penjualan Tray%');
                    });
                } else {
                    $q->where(function ($sq) {
                        $sq->where('name', 'like', 'Penjualan Telur%')
                          ->orWhere('name', 'like', 'Penjualan Pakan%')
                          ->orWhere('name', 'like', 'Penjualan Obat%')
                          ->orWhere('name', 'like', 'Penjualan Eggtray%')
                          ->orWhere('name', 'like', 'Penjualan Tray%')
                          ->orWhere('name', 'like', 'Penjualan%');
                    });
                }
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
            'message' => 'Data Penjualan (keluar) berhasil diambil',
            'data' => $transaksi->items(),
            'pagination' => [
                'current_page' => $transaksi->currentPage(),
                'last_page' => $transaksi->lastPage(),
                'per_page' => $transaksi->perPage(),
                'total' => $transaksi->total(),
            ]
        ]);
    }

    /**
     * Data Retur
     */
    public function retur(Request $request)
    {
        $limit = $request->query('limit', 500);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Transaksi::with(['client', 'user', 'details.barang', 'details.kategori'])
            ->whereHas('details.kategori', function ($q) {
                $q->where('name', 'like', '%Retur%')
                  ->orWhere('name', 'like', '%Kembali%');
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
