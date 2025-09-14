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

    Volt::route('/telur', 'telur.index');
    Volt::route('/telur/create', 'telur.create');
    Volt::route('/telur/{telur}/edit', 'telur.edit');

    Volt::route('/pakan', 'pakan.index');
    Volt::route('/pakan/create', 'pakan.create');
    Volt::route('/pakan/{pakan}/edit', 'pakan.edit');

    Volt::route('/lainnya', 'lainnya.index');
    Volt::route('/lainnya/create', 'lainnya.create');
    Volt::route('/lainnya/{lainnya}/edit', 'lainnya.edit');

    Volt::route('/tunai', 'tunai.index');
    Volt::route('/tunai/create', 'tunai.create');
    Volt::route('/tunai/{tunai}/edit', 'tunai.edit');

    Volt::route('/transfer', 'transfer.index');
    Volt::route('/transfer/create', 'transfer.create');
    Volt::route('/transfer/{transfer}/edit', 'transfer.edit');
});

