<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use PhpParser\Node\Expr\Cast\Void_;

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

    Volt::route('/roles', 'roles.index');

    Volt::route('/users', 'users.index');
    Volt::route('/users/create', 'users.create');
    Volt::route('/users/{user}/edit', 'users.edit');

    Volt::route('/barangs', 'barangs.index');
    Volt::route('/barangs/create', 'barangs.create');
    Volt::route('/barangs/{barang}/edit', 'barangs.edit');

    Volt::route('/jenisbarangs', 'jenisbarangs.index');

    Volt::route('/satuans', 'satuans.index');

    Volt::route('/clients', 'clients.index');

    Volt::route('/kategoris', 'kategoris.index');

    Volt::route('/transaksis', 'transaksis.index');
    Volt::route('/transaksis/create', 'transaksis.create');
    Volt::route('/transaksis/{transaksi}/edit', 'transaksis.edit'); 

    Volt::route('/telur-masuk', 'telur-masuk.index');
    Volt::route('/telur-masuk/create', 'telur-masuk.create');
    Volt::route('/telur-masuk/{transaksi}/edit', 'telur-masuk.edit');
    Volt::route('/telur-masuk/{transaksi}/show', 'telur-masuk.show');

    Volt::route('/telur-keluar', 'telur-keluar.index');
    Volt::route('/telur-keluar/create', 'telur-keluar.create');
    Volt::route('/telur-keluar/{transaksi}/edit', 'telur-keluar.edit');
    Volt::route('/telur-keluar/{transaksi}/show', 'telur-keluar.show');

    Volt::route('/pakan', 'pakan.index');
    Volt::route('/pakan/create', 'pakan.create');
    Volt::route('/pakan/{transaksi}/edit', 'pakan.edit');
    Volt::route('/pakan/{transaksi}/show', 'pakan.show');

    Volt::route('/obat', 'obat.index');
    Volt::route('/obat/create', 'obat.create');
    Volt::route('/obat/{transaksi}/edit', 'obat.edit');
    Volt::route('/obat/{transaksi}/show', 'obat.show');

    Volt::route('/lainnya', 'lainnya.index');
    Volt::route('/lainnya/create', 'lainnya.create');
    Volt::route('/lainnya/{transaksi}/edit', 'lainnya.edit');
    Volt::route('/lainnya/{transaksi}/show', 'lainnya.show');

    Volt::route('/tunai', 'tunai.index');
    Volt::route('/tunai/create', 'tunai.create');
    Volt::route('/tunai/{transaksi}/edit', 'tunai.edit');
    Volt::route('/tunai/{transaksi}/show', 'tunai.show');

    Volt::route('/beban', 'beban.index');
    Volt::route('/beban/create', 'beban.create');
    Volt::route('/beban/{transaksi}/edit', 'beban.edit');
    Volt::route('/beban/{transaksi}/show', 'beban.show');

    Volt::route('/bank', 'bank.index');
    Volt::route('/bank/create', 'bank.create');
    Volt::route('/bank/{transaksi}/edit', 'bank.edit');
    Volt::route('/bank/{transaksi}/show', 'bank.show');

    Volt::route('/piutang', 'piutang.index');
    Volt::route('/piutang/create', 'piutang.create');
    Volt::route('/piutang/{transaksi}/edit', 'piutang.edit');
    Volt::route('/piutang/{transaksi}/show', 'piutang.show');

    Volt::route('/hutang', 'hutang.index');
    Volt::route('/hutang/create', 'hutang.create');
    Volt::route('/hutang/{transaksi}/edit', 'hutang.edit');
    Volt::route('/hutang/{transaksi}/show', 'hutang.show');

    Volt::route('/laporan-labarugi', 'laporan.labarugi');
    Volt::route('/laporan-neraca-saldo', 'laporan.neraca-saldo');
});

