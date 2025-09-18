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

    Volt::route('/beban-tunai', 'beban-tunai.index');
    Volt::route('/beban-tunai/create', 'beban-tunai.create');
    Volt::route('/beban-tunai/{transaksi}/edit', 'beban-tunai.edit');
    Volt::route('/beban-tunai/{transaksi}/show', 'beban-tunai.show');

    Volt::route('/utang-tunai', 'utang-tunai.index');
    Volt::route('/utang-tunai/create', 'utang-tunai.create');
    Volt::route('/utang-tunai/{transaksi}/edit', 'utang-tunai.edit');
    Volt::route('/utang-tunai/{transaksi}/show', 'utang-tunai.show');

    Volt::route('/bank', 'bank.index');
    Volt::route('/bank/create', 'bank.create');
    Volt::route('/bank/{transaksi}/edit', 'bank.edit');
    Volt::route('/bank/{transaksi}/show', 'bank.show');

    Volt::route('/beban-bank', 'beban-bank.index');
    Volt::route('/beban-bank/create', 'beban-bank.create');
    Volt::route('/beban-bank/{transaksi}/edit', 'beban-bank.edit');
    Volt::route('/beban-bank/{transaksi}/show', 'beban-bank.show');

    Volt::route('/utang-bank', 'utang-bank.index');
    Volt::route('/utang-bank/create', 'utang-bank.create');
    Volt::route('/utang-bank/{transaksi}/edit', 'utang-bank.edit');
    Volt::route('/utang-bank/{transaksi}/show', 'utang-bank.show');

    Volt::route('/bon', 'bon.index');
    Volt::route('/bon/create', 'bon.create');
    Volt::route('/bon/{transaksi}/edit', 'bon.edit');
    Volt::route('/bon/{transaksi}/show', 'bon.show');

    Volt::route('/titipan', 'titipan.index');
    Volt::route('/titipan/create', 'titipan.create');
    Volt::route('/titipan/{transaksi}/edit', 'titipan.edit');
    Volt::route('/titipan/{transaksi}/show', 'titipan.show');
});

