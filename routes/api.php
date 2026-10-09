<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MasterDataApiController;
use App\Http\Controllers\Api\TransaksiApiController;
use App\Http\Controllers\Api\KeuanganApiController;

Route::middleware(['api_key'])->prefix('v1')->group(function () {
    // Master Data Endpoints
    Route::get('/barang', [MasterDataApiController::class, 'barang']);
    Route::get('/jenis-barang', [MasterDataApiController::class, 'jenisBarang']);
    Route::get('/client', [MasterDataApiController::class, 'client']);
    Route::get('/akun', [MasterDataApiController::class, 'akun']);
    Route::get('/kategori-akun', [MasterDataApiController::class, 'kategoriAkun']);

    // Transaksi Endpoints
    Route::get('/pembelian', [TransaksiApiController::class, 'pembelian']);
    Route::get('/penjualan', [TransaksiApiController::class, 'penjualan']);
    Route::get('/retur', [TransaksiApiController::class, 'retur']);

    // Keuangan & Akuntansi Endpoints
    Route::get('/hutang', [KeuanganApiController::class, 'hutang']);
    Route::get('/piutang', [KeuanganApiController::class, 'piutang']);
    Route::get('/beban', [KeuanganApiController::class, 'beban']);
    Route::get('/pendapatan-lainnya', [KeuanganApiController::class, 'pendapatanLainnya']);
    Route::get('/kas-bank', [KeuanganApiController::class, 'kasBank']);
});
