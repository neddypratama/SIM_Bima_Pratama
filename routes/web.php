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

});

