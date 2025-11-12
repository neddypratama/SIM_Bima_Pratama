<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Middleware\RoleMiddleware;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Volt::route('/login', 'auth/login')->name('login');
});

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/
Route::get('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Dashboard & Profile
    Volt::route('/', 'index');
    Volt::route('/profile', 'auth/profile');

    /*
    |--------------------------------------------------------------------------
    | Admin (Role 1)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:1')->group(function () {
        Volt::route('/roles', 'roles.index');

        Volt::route('/users', 'users.index');
        Volt::route('/users/create', 'users.create');
        Volt::route('/users/{user}/edit', 'users.edit');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin & Kasir (Role 1,2)
    |--------------------------------------------------------------------------
    */
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

        // Stok Telur
        Volt::route('/stok-telur', 'stok-telur.index');
        Volt::route('/stok-telur/create', 'stok-telur.create');
        Volt::route('/stok-telur/{stok}/edit', 'stok-telur.edit');
        Volt::route('/stok-telur/{stok}/show', 'stok-telur.show');

        // Stok Pakan
        Volt::route('/stok-pakan', 'stok-pakan.index');
        Volt::route('/stok-pakan/create', 'stok-pakan.create');
        Volt::route('/stok-pakan/{stok}/edit', 'stok-pakan.edit');
        Volt::route('/stok-pakan/{stok}/show', 'stok-pakan.show');

        // Stok Obat
        Volt::route('/stok-obat', 'stok-obat.index');
        Volt::route('/stok-obat/create', 'stok-obat.create');
        Volt::route('/stok-obat/{stok}/edit', 'stok-obat.edit');
        Volt::route('/stok-obat/{stok}/show', 'stok-obat.show');

        // Stok Tray
        Volt::route('/stok-tray', 'stok-tray.index');
        Volt::route('/stok-tray/create', 'stok-tray.create');
        Volt::route('/stok-tray/{stok}/edit', 'stok-tray.edit');
        Volt::route('/stok-tray/{stok}/show', 'stok-tray.show');

        // Transport
        Volt::route('/transport', 'transport.index');
        Volt::route('/transport/create', 'transport.create');
        Volt::route('/transport/{truk}/edit', 'transport.edit');
        Volt::route('/transport/{truk}/show', 'transport.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Truk & Kotor (Role 1,2,7)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:1,2,7')->group(function () {
        Volt::route('/truk', 'truk.index');
        Volt::route('/kotor', 'kotor.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Pembelian Telur (Role 1,3)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:1,3')->group(function () {
        Volt::route('/telur-masuk', 'telur-masuk.index');
        Volt::route('/telur-masuk/create', 'telur-masuk.create');
        Volt::route('/telur-masuk/{transaksi}/edit', 'telur-masuk.edit');
        Volt::route('/telur-masuk/{transaksi}/show', 'telur-masuk.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Laporan Telur & Tray (Role 1,3,6,7)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:1,3,6,7')->group(function () {
        Volt::route('/laporan-telur', 'telur.index');
        Volt::route('/laporan-tray', 'tray.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Pakan & Obat (Role 1,4)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:1,4')->group(function () {
        // Pakan
        Volt::route('/laporan-pakan', 'pakan.index');
        Volt::route('/sentrat-keluar', 'sentrat-keluar.index');
        Volt::route('/sentrat-keluar/create', 'sentrat-keluar.create');
        Volt::route('/sentrat-keluar/{transaksi}/edit', 'sentrat-keluar.edit');
        Volt::route('/sentrat-keluar/{transaksi}/show', 'sentrat-keluar.show');

        // Obat
        Volt::route('/laporan-obat', 'obat.index');
        Volt::route('/obat-keluar', 'obat-keluar.index');
        Volt::route('/obat-keluar/create', 'obat-keluar.create');
        Volt::route('/obat-keluar/{transaksi}/edit', 'obat-keluar.edit');
        Volt::route('/obat-keluar/{transaksi}/show', 'obat-keluar.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Kas Tunai (Role 1,5)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:1,5')->group(function () {
        Volt::route('/tunai', 'tunai.index');
        Volt::route('/tunai/create', 'tunai.create');
        Volt::route('/tunai/{transaksi}/edit', 'tunai.edit');
        Volt::route('/tunai/{transaksi}/show', 'tunai.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Kas Umum, Bon, Piutang, Hutang (Role 1,5,6,7)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:1,5,6,7')->group(function () {
        Volt::route('/kas', 'kas.index');
        Volt::route('/bon-titipan', 'bon-titipan.index');

        // Piutang
        Volt::route('/piutang', 'piutang.index');
        Volt::route('/piutang/create', 'piutang.create');
        Volt::route('/piutang/{transaksi}/edit', 'piutang.edit');
        Volt::route('/piutang/{transaksi}/show', 'piutang.show');

        // Hutang
        Volt::route('/hutang', 'hutang.index');
        Volt::route('/hutang/create', 'hutang.create');
        Volt::route('/hutang/{transaksi}/edit', 'hutang.edit');
        Volt::route('/hutang/{transaksi}/show', 'hutang.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Kas Bank & Penjualan Telur/Tray (Role 1,6)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:1,6')->group(function () {
        // Kas Bank (Transfer)
        Volt::route('/transfer', 'transfer.index');
        Volt::route('/transfer/create', 'transfer.create');
        Volt::route('/transfer/{transaksi}/edit', 'transfer.edit');
        Volt::route('/transfer/{transaksi}/show', 'transfer.show');

        // Telur Keluar
        Volt::route('/telur-keluar', 'telur-keluar.index');
        Volt::route('/telur-keluar/create', 'telur-keluar.create');
        Volt::route('/telur-keluar/{transaksi}/edit', 'telur-keluar.edit');
        Volt::route('/telur-keluar/{transaksi}/show', 'telur-keluar.show');

        // Tray Masuk & Keluar
        Volt::route('/tray-masuk', 'tray-masuk.index');
        Volt::route('/tray-masuk/create', 'tray-masuk.create');
        Volt::route('/tray-masuk/{transaksi}/edit', 'tray-masuk.edit');
        Volt::route('/tray-masuk/{transaksi}/show', 'tray-masuk.show');

        Volt::route('/tray-keluar', 'tray-keluar.index');
        Volt::route('/tray-keluar/create', 'tray-keluar.create');
        Volt::route('/tray-keluar/{transaksi}/edit', 'tray-keluar.edit');
        Volt::route('/tray-keluar/{transaksi}/show', 'tray-keluar.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Pakan & Obat Masuk (Role 1,4,6)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:1,4,6')->group(function () {
        // Sentrat
        Volt::route('/sentrat-masuk', 'sentrat-masuk.index');
        Volt::route('/sentrat-masuk/create', 'sentrat-masuk.create');
        Volt::route('/sentrat-masuk/{transaksi}/edit', 'sentrat-masuk.edit');
        Volt::route('/sentrat-masuk/{transaksi}/show', 'sentrat-masuk.show');

        // Obat
        Volt::route('/obat-masuk', 'obat-masuk.index');
        Volt::route('/obat-masuk/create', 'obat-masuk.create');
        Volt::route('/obat-masuk/{transaksi}/edit', 'obat-masuk.edit');
        Volt::route('/obat-masuk/{transaksi}/show', 'obat-masuk.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Pendapatan Lain & Beban (Role 1,5,6)
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Laporan Akhir (Role 1,2,7)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:1,7')->group(function () {
        Volt::route('/laporan-labarugi', 'laporan.labarugi');
        Volt::route('/laporan-neraca-saldo', 'laporan.neraca-saldo');
        Volt::route('/laporan-aset', 'laporan.aset');
    });
});
