<?php

use Livewire\Volt\Volt;

Volt::route('/', 'index');
Volt::route('/users', 'users.index');
Volt::route('/users/create', 'users.create');
Volt::route('/users/{user}/edit', 'users.edit');

Volt::route('/barangs', 'barangs.index');
Volt::route('/barangs/create', 'barangs.create');
Volt::route('/barangs/{barang}/edit', 'barangs.edit');

Volt::route('/kategori', 'kategori.index');

Volt::route('/roles', 'roles.index');
