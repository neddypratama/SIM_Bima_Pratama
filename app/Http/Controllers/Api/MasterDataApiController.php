<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\Client;
use Illuminate\Http\Request;

class MasterDataApiController extends Controller
{
    public function barang(Request $request)
    {
        $limit = $request->query('limit', 500);
        $search = $request->query('search');

        $query = Barang::with('jenis');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $barangs = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Data Barang berhasil diambil',
            'data' => $barangs->items(),
            'pagination' => [
                'current_page' => $barangs->currentPage(),
                'last_page' => $barangs->lastPage(),
                'per_page' => $barangs->perPage(),
                'total' => $barangs->total(),
            ]
        ]);
    }

    public function jenisBarang(Request $request)
    {
        $limit = $request->query('limit', 500);
        $search = $request->query('search');

        $query = JenisBarang::with('kategori');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $jenis = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Data Jenis Barang berhasil diambil',
            'data' => $jenis->items(),
            'pagination' => [
                'current_page' => $jenis->currentPage(),
                'last_page' => $jenis->lastPage(),
                'per_page' => $jenis->perPage(),
                'total' => $jenis->total(),
            ]
        ]);
    }

    public function client(Request $request)
    {
        $limit = $request->query('limit', 500);
        $type = $request->query('type');
        $search = $request->query('search');

        $query = Client::query();

        if ($type) {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $clients = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Data Client berhasil diambil',
            'data' => $clients->items(),
            'pagination' => [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ]
        ]);
    }
}
