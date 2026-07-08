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
    | Admin (Role 8,1)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:8,1')->group(function () {
        Volt::route('/roles', 'roles.index');

        Volt::route('/users', 'users.index');
        Volt::route('/users/create', 'users.create');
        Volt::route('/users/{user}/edit', 'users.edit');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin & Kasir (Role 8,1,2)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:8,1,2')->group(function () {
        Volt::route('/barangs', 'barangs.index');
        Volt::route('/barangs/create', 'barangs.create');
        Volt::route('/barangs/{barang}/edit', 'barangs.edit');

        Volt::route('/jenisbarangs', 'jenisbarangs.index');
        Volt::route('/satuans', 'satuans.index');
        Volt::route('/clients', 'clients.index');
        Volt::route('/kategoris', 'kategoris.index');
        Volt::route('/detail-kategoris', 'detail-kategoris.index');

        Volt::route('/transaksis', 'transaksis.index');
        Volt::route('/transaksis/{transaksi}/show', 'transaksis.show');

        Volt::route('/kotor', 'kotor.index');

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
    });

    /*
    |--------------------------------------------------------------------------
    | Truk & Kotor (Role 8,1,2,7)
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:8')->group(function () {
        Volt::route('/fix1', 'fix.fix1');
        Volt::route('/fix2', 'fix.fix2');
        Volt::route('/fix3', 'fix.fix3');

        // Stok
        Volt::route('/penambahan-stok', 'penambahan-stok.index');
        Volt::route('/penambahan-stok/create', 'penambahan-stok.create');
        Volt::route('/penambahan-stok/{batch}/edit', 'penambahan-stok.edit');
        Volt::route('/penambahan-stok/{batch}/show', 'penambahan-stok.show');
    });

    
    Route::middleware('role:8,1,2,7')->group(function () {
        Volt::route('/deby', 'deby.index');
        Volt::route('/deby/create', 'deby.create');
        Volt::route('/deby/{transaksi}/edit', 'deby.edit');
        Volt::route('/deby/{transaksi}/show', 'deby.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Pembelian Telur (Role 8,1,3)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:8,1,3')->group(function () {
        Volt::route('/telur-masuk', 'telur-masuk.index');
        Volt::route('/telur-masuk/create', 'telur-masuk.create');
        Volt::route('/telur-masuk/{transaksi}/edit', 'telur-masuk.edit');
        Volt::route('/telur-masuk/{transaksi}/show', 'telur-masuk.show');

        Volt::route('/telur-kembali', 'telur-kembali.index');
        Volt::route('/telur-kembali/create', 'telur-kembali.create');
        Volt::route('/telur-kembali/{transaksi}/edit', 'telur-kembali.edit');
        Volt::route('/telur-kembali/{transaksi}/show', 'telur-kembali.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Laporan Telur & Tray (Role 8,1,3,6,7)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:8,1,3,6,7')->group(function () {
        Volt::route('/laporan-telur', 'telur.index');
        Volt::route('/laporan-tray', 'tray.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Pakan & Obat (Role 8,1,4)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:8,1,4')->group(function () {
        // Pakan
        Volt::route('/laporan-pakan', 'pakan.index');
        Volt::route('/sentrat-keluar', 'sentrat-keluar.index');
        Volt::route('/sentrat-keluar/create', 'sentrat-keluar.create');
        Volt::route('/sentrat-keluar/{transaksi}/edit', 'sentrat-keluar.edit');
        Volt::route('/sentrat-keluar/{transaksi}/show', 'sentrat-keluar.show');

        Volt::route('/sentrat-return', 'sentrat-return.index');
        Volt::route('/sentrat-return/create', 'sentrat-return.create');
        Volt::route('/sentrat-return/{transaksi}/edit', 'sentrat-return.edit');
        Volt::route('/sentrat-return/{transaksi}/show', 'sentrat-return.detail');

        // Obat
        Volt::route('/laporan-obat', 'obat.index');
        Volt::route('/obat-keluar', 'obat-keluar.index');
        Volt::route('/obat-keluar/create', 'obat-keluar.create');
        Volt::route('/obat-keluar/{transaksi}/edit', 'obat-keluar.edit');
        Volt::route('/obat-keluar/{transaksi}/show', 'obat-keluar.show');

        Volt::route('/obat-return', 'obat-return.index');
        Volt::route('/obat-return/create', 'obat-return.create');
        Volt::route('/obat-return/{transaksi}/edit', 'obat-return.edit');
        Volt::route('/obat-return/{transaksi}/show', 'obat-return.detail');
    });

    /*
    |--------------------------------------------------------------------------
    | Kas Tunai (Role 8,1,5)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:8,1,5')->group(function () {
        Volt::route('/tunai', 'tunai.index');
        Volt::route('/tunai/create', 'tunai.create');
        Volt::route('/tunai/{transaksi}/edit', 'tunai.edit');
        Volt::route('/tunai/{transaksi}/show', 'tunai.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Kas Umum, Bon, Piutang, Hutang (Role 8,1,5,6,7)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:8,1,5,6,7')->group(function () {
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
    | Kas Bank & Penjualan Telur/Tray (Role 8,1,6)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:8,1,6,5')->group(function () {
        // Kas Bank (Transfer)
        Volt::route('/transfer', 'transfer.index');
        Volt::route('/transfer/create', 'transfer.create');
        Volt::route('/transfer/{transaksi}/edit', 'transfer.edit');
        Volt::route('/transfer/{transaksi}/show', 'transfer.show');
    });

    Route::middleware('role:8,1,6')->group(function () {
        // Telur Keluar
        Volt::route('/telur-keluar', 'telur-keluar.index');
        Volt::route('/telur-keluar/create', 'telur-keluar.create');
        Volt::route('/telur-keluar/{transaksi}/edit', 'telur-keluar.edit');
        Volt::route('/telur-keluar/{transaksi}/show', 'telur-keluar.show');

        Volt::route('/telur-return', 'telur-return.index');
        Volt::route('/telur-return/create', 'telur-return.create');
        Volt::route('/telur-return/{transaksi}/edit', 'telur-return.edit');
        Volt::route('/telur-return/{transaksi}/show', 'telur-return.detail');

        // Tray Masuk & Keluar
        Volt::route('/tray-masuk', 'tray-masuk.index');
        Volt::route('/tray-masuk/create', 'tray-masuk.create');
        Volt::route('/tray-masuk/{transaksi}/edit', 'tray-masuk.edit');
        Volt::route('/tray-masuk/{transaksi}/show', 'tray-masuk.show');

        Volt::route('/tray-kembali', 'tray-kembali.index');
        Volt::route('/tray-kembali/create', 'tray-kembali.create');
        Volt::route('/tray-kembali/{transaksi}/edit', 'tray-kembali.edit');
        Volt::route('/tray-kembali/{transaksi}/show', 'tray-kembali.show');

        Volt::route('/tray-keluar', 'tray-keluar.index');
        Volt::route('/tray-keluar/create', 'tray-keluar.create');
        Volt::route('/tray-keluar/{transaksi}/edit', 'tray-keluar.edit');
        Volt::route('/tray-keluar/{transaksi}/show', 'tray-keluar.show');

        Volt::route('/tray-return', 'tray-return.index');
        Volt::route('/tray-return/create', 'tray-return.create');
        Volt::route('/tray-return/{transaksi}/edit', 'tray-return.edit');
        Volt::route('/tray-return/{transaksi}/show', 'tray-return.detail');
    });

    /*
    |--------------------------------------------------------------------------
    | Pakan & Obat Masuk (Role 8,1,4,6)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:8,1,4,6')->group(function () {
        // Sentrat
        Volt::route('/sentrat-masuk', 'sentrat-masuk.index');
        Volt::route('/sentrat-masuk/create', 'sentrat-masuk.create');
        Volt::route('/sentrat-masuk/{transaksi}/edit', 'sentrat-masuk.edit');
        Volt::route('/sentrat-masuk/{transaksi}/show', 'sentrat-masuk.show');

        Volt::route('/sentrat-kembali', 'sentrat-kembali.index');
        Volt::route('/sentrat-kembali/create', 'sentrat-kembali.create');
        Volt::route('/sentrat-kembali/{transaksi}/edit', 'sentrat-kembali.edit');
        Volt::route('/sentrat-kembali/{transaksi}/show', 'sentrat-kembali.show');

        // Obat
        Volt::route('/obat-masuk', 'obat-masuk.index');
        Volt::route('/obat-masuk/create', 'obat-masuk.create');
        Volt::route('/obat-masuk/{transaksi}/edit', 'obat-masuk.edit');
        Volt::route('/obat-masuk/{transaksi}/show', 'obat-masuk.show');

        Volt::route('/obat-kembali', 'obat-kembali.index');
        Volt::route('/obat-kembali/create', 'obat-kembali.create');
        Volt::route('/obat-kembali/{transaksi}/edit', 'obat-kembali.edit');
        Volt::route('/obat-kembali/{transaksi}/show', 'obat-kembali.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Pendapatan Lain & Beban (Role 8,1,5,6)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:8,1,5,6')->group(function () {
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
    | Laporan Akhir (Role 8,1,2,7)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:8,1,7')->group(function () {
        Volt::route('/laporan-labarugi', 'laporan.labarugi');
        Volt::route('/laporan-neraca-saldo', 'laporan.neraca-saldo');
        Volt::route('/laporan-aset', 'laporan.aset');
        Volt::route('/laporan-curah', 'laporan.curah');
    });
});
