<?php

use Livewire\Volt\Volt;

Volt::route('/', 'users.index');
Volt::route('/users/create', 'users.create');
Volt::route('/users/{user}/edit', 'users.edit');

Volt::route('/barangs', 'barangs.index');
Volt::route('/barangs/create', 'barangs.create');
Volt::route('/barangs/{barang}/edit', 'barangs.edit');

Volt::route('/kategoris', 'kategoris.index');
Volt::route('/kategoris/create', 'kategoris.create');
Volt::route('/kategoris/{kategori}/edit', 'kategoris.edit');

Volt::route('/roles', 'roles.index');
Volt::route('/roles/create', 'roles.create');
Volt::route('/roles/{role}/edit', 'roles.edit');
