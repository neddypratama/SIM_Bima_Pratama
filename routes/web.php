<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Middleware\RoleMiddleware;

Route::middleware('guest')->group(function () {
    Volt::route('/login', 'auth/login')->name('login');
});

// Define the logout
Route::get('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
 
    return redirect('/');
});

Route::middleware('auth')->group(function() {
    Volt::route('/', 'index');
    Volt::route('/profile', 'auth/profile');

    Route::middleware('role:1')->group(function () {
        Volt::route('/roles', 'roles.index');

        Volt::route('/users', 'users.index');
        Volt::route('/users/create', 'users.create');
        Volt::route('/users/{user}/edit', 'users.edit');
    });

    Route::middleware('role:1,2')->group(function () {
        Volt::route('/barangs', 'barangs.index');
        Volt::route('/barangs/create', 'barangs.create');
        Volt::route('/barangs/{barang}/edit', 'barangs.edit');

        Volt::route('/jenisbarangs', 'jenisbarangs.index');

        Volt::route('/satuans', 'satuans.index');

        Volt::route('/clients', 'clients.index');

        Volt::route('/kategoris', 'kategoris.index');

        Volt::route('/transaksis', 'transaksis.index');
        Volt::route('/transaksis/{transaksi}/show', 'transaksis.show'); 

        Volt::route('/stok', 'stok.index');
        Volt::route('/stok/create', 'stok.create');
        Volt::route('/stok/{stok}/edit', 'stok.edit');
        Volt::route('/stok/{stok}/show', 'stok.show');

    });

    // User Pembelian Telur 
    Route::middleware('role:1,3')->group(function () {
        Volt::route('/telur-masuk', 'telur-masuk.index');
        Volt::route('/telur-masuk/create', 'telur-masuk.create');
        Volt::route('/telur-masuk/{transaksi}/edit', 'telur-masuk.edit');
        Volt::route('/telur-masuk/{transaksi}/show', 'telur-masuk.show');
    });

    // User Pakan dan Obat
    Route::middleware('role:1,4')->group(function () {
        Volt::route('/sentrat-keluar', 'sentrat-keluar.index');
        Volt::route('/sentrat-keluar/create', 'sentrat-keluar.create');
        Volt::route('/sentrat-keluar/{transaksi}/edit', 'sentrat-keluar.edit');
        Volt::route('/sentrat-keluar/{transaksi}/show', 'sentrat-keluar.show');
    
        Volt::route('/obat-masuk', 'obat-masuk.index');
        Volt::route('/obat-masuk/create', 'obat-masuk.create');
        Volt::route('/obat-masuk/{transaksi}/edit', 'obat-masuk.edit');
        Volt::route('/obat-masuk/{transaksi}/show', 'obat-masuk.show');
    
        Volt::route('/obat-keluar', 'obat-keluar.index');
        Volt::route('/obat-keluar/create', 'obat-keluar.create');
        Volt::route('/obat-keluar/{transaksi}/edit', 'obat-keluar.edit');
        Volt::route('/obat-keluar/{transaksi}/show', 'obat-keluar.show');
    });

    // User Kas Tunai
    Route::middleware('role:1,5')->group(function () {
        Volt::route('/tunai', 'tunai.index');
        Volt::route('/tunai/create', 'tunai.create');
        Volt::route('/tunai/{transaksi}/edit', 'tunai.edit');
        Volt::route('/tunai/{transaksi}/show', 'tunai.show');
    });

    Route::middleware('role:1,5,6')->group(function () {
        Volt::route('/piutang', 'piutang.index');
        Volt::route('/piutang/create', 'piutang.create');
        Volt::route('/piutang/{transaksi}/edit', 'piutang.edit');
        Volt::route('/piutang/{transaksi}/show', 'piutang.show');
    
        Volt::route('/hutang', 'hutang.index');
        Volt::route('/hutang/create', 'hutang.create');
        Volt::route('/hutang/{transaksi}/edit', 'hutang.edit');
        Volt::route('/hutang/{transaksi}/show', 'hutang.show');
    });


    // User Kas Bank
    Route::middleware('role:1,6')->group(function () {
        Volt::route('/transfer', 'transfer.index');
        Volt::route('/transfer/create', 'transfer.create');
        Volt::route('/transfer/{transaksi}/edit', 'transfer.edit');
        Volt::route('/transfer/{transaksi}/show', 'transfer.show');

        // User Penjualan Telur dan Tray
        Volt::route('/telur-keluar', 'telur-keluar.index');
        Volt::route('/telur-keluar/create', 'telur-keluar.create');
        Volt::route('/telur-keluar/{transaksi}/edit', 'telur-keluar.edit');
        Volt::route('/telur-keluar/{transaksi}/show', 'telur-keluar.show');

        Volt::route('/tray-masuk', 'tray-masuk.index');
        Volt::route('/tray-masuk/create', 'tray-masuk.create');
        Volt::route('/tray-masuk/{transaksi}/edit', 'tray-masuk.edit');
        Volt::route('/tray-masuk/{transaksi}/show', 'tray-masuk.show');
    
        Volt::route('/tray-keluar', 'tray-keluar.index');
        Volt::route('/tray-keluar/create', 'tray-keluar.create');
        Volt::route('/tray-keluar/{transaksi}/edit', 'tray-keluar.edit');
        Volt::route('/tray-keluar/{transaksi}/show', 'tray-keluar.show');
    });

    Route::middleware('role:1,4,6')->group(function () {
        Volt::route('/sentrat-masuk', 'sentrat-masuk.index');
        Volt::route('/sentrat-masuk/create', 'sentrat-masuk.create');
        Volt::route('/sentrat-masuk/{transaksi}/edit', 'sentrat-masuk.edit');
        Volt::route('/sentrat-masuk/{transaksi}/show', 'sentrat-masuk.show');
    });

    // User Kas Tunai dan Kas Bank pendapatan lainnya dan beban
    Route::middleware('role:1,5,6')->group(function () {
        Volt::route('/lainnya', 'lainnya.index');
        Volt::route('/lainnya/create', 'lainnya.create');
        Volt::route('/lainnya/{transaksi}/edit', 'lainnya.edit');
        Volt::route('/lainnya/{transaksi}/show', 'lainnya.show');
    
    
        Volt::route('/beban', 'beban.index');
        Volt::route('/beban/create', 'beban.create');
        Volt::route('/beban/{transaksi}/edit', 'beban.edit');
        Volt::route('/beban/{transaksi}/show', 'beban.show');
    });

    // User Rekap dan Manager
    Route::middleware('role:1,2,7')->group(function () {
        Volt::route('/laporan-labarugi', 'laporan.labarugi');
        Volt::route('/laporan-neraca-saldo', 'laporan.neraca-saldo');
    });
});

