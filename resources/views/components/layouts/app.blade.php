<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' - ' . config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Cropper.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />

    {{-- TinyMCE --}}
    <script src="https://cdn.tiny.cloud/1/zj7w29mcgsahkxloyg71v6365yxaoa4ey1ur6l45pnb63v42/tinymce/6/tinymce.min.js"
        referrerpolicy="origin"></script>

    {{--  Currency  --}}
    <script type="text/javascript" src="https://cdn.jsdelivr.net/gh/robsontenorio/mary@0.44.2/libs/currency/currency.js">
    </script>

    {{-- Chart.js  --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>

<body class="min-h-screen font-sans antialiased bg-base-200">

    {{-- NAVBAR mobile only --}}
    <x-nav sticky class="lg:hidden">
        <x-slot:brand>
            <x-app-brand />
        </x-slot:brand>
        <x-slot:actions>
            <label for="main-drawer" class="lg:hidden me-3">
                <x-icon name="o-bars-3" class="cursor-pointer" />
            </label>
        </x-slot:actions>
    </x-nav>

    {{-- MAIN --}}
    <x-main>
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">

            {{-- BRAND --}}
            <x-app-brand class="px-5 pt-4" />

            {{-- MENU --}}
            <x-menu activate-by-route>

                {{-- User --}}
                @if ($user = auth()->user())
                    <x-menu-separator />
                    <x-list-item :item="$user" value="name" sub-value="email" no-separator no-hover
                        class="-mx-2 !-my-2 rounded">
                        <x-slot:actions>
                            <x-button icon="o-power" class="btn-circle btn-ghost btn-xs" tooltip-left="Logout"
                                no-wire-navigate link="/logout" />
                        </x-slot:actions>
                    </x-list-item>
                    <x-menu-separator />
                @endif

                <x-menu-item title="Dashboard" icon="o-sparkles" link="/" />

                {{-- ✅ User Management hanya untuk role 1 (Admin) --}}
                @if (auth()->user()->role_id === 1)
                    <x-menu-sub title="User Management" icon="fas.users-gear">
                        <x-menu-item title="Users" icon="o-user" link="/users" />
                        <x-menu-item title="Roles" icon="o-shield-check" link="/roles" />
                    </x-menu-sub>
                @endif

                {{-- ✅ Master Data hanya untuk role 1 dan 2 --}}
                @if (in_array(auth()->user()->role_id, [1, 2]))
                    <x-menu-sub title="Master Data" icon="fas.database">
                        <x-menu-item title="Jenis Barangs" icon="o-archive-box" link="/jenisbarangs" />
                        <x-menu-item title="Barangs" icon="fas.box" link="/barangs" />
                        <x-menu-item title="Satuans" icon="o-scale" link="/satuans" />
                        <x-menu-item title="Kategoris" icon="o-rectangle-group" link="/kategoris" />
                        <x-menu-item title="Clients" icon="o-users" link="/clients" />
                        <x-menu-item title="Transaksis" icon="o-arrow-path" link="/transaksis" />
                    </x-menu-sub>
                @endif

                {{-- ✅ Transactions untuk role sesuai route --}}
                @if (in_array(auth()->user()->role_id, [1, 3]))
                    <x-menu-sub title="Telur & Tray" icon="fas.egg">
                        <x-menu-item title="Pembelian Telur" link="/telur-masuk" />
                        <x-menu-item title="Penjualan Telur" link="/telur-keluar" />
                        <x-menu-item title="Pembelian Tray" link="/tray-masuk" />
                        <x-menu-item title="Penjualan Tray" link="/tray-keluar" />
                    </x-menu-sub>
                @endif

                @if (in_array(auth()->user()->role_id, [1, 4]))
                    <x-menu-sub title="Pakan & Obat" icon="o-beaker">
                        <x-menu-item title="Pembelian Sentrat" link="/sentrat-masuk" />
                        <x-menu-item title="Penjualan Sentrat" link="/sentrat-keluar" />
                        <x-menu-item title="Pembelian Obat" link="/obat-masuk" />
                        <x-menu-item title="Penjualan Obat" link="/obat-keluar" />
                    </x-menu-sub>
                @endif

                <x-menu-sub title="Kas" icon="fas.building-columns">
                    @if (in_array(auth()->user()->role_id, [1, 5]))
                        <x-menu-item title="Transaksi Kas Tunai" link="/tunai" />
                    @endif
                    @if (in_array(auth()->user()->role_id, [1, 6]))
                        <x-menu-item title="Transaksi Bank Transfer" link="/transfer" />
                    @endif
                </x-menu-sub>

                @if (in_array(auth()->user()->role_id, [1, 5, 6]))
                    <x-menu-sub title="Pendapatan & Beban" icon="o-currency-dollar">
                        <x-menu-item title="Transaksi Lainnya" link="/lainnya" />
                        <x-menu-item title="Transaksi Beban" link="/beban" />
                    </x-menu-sub>
                @endif

                @if (in_array(auth()->user()->role_id, [1, 7]))
                    <x-menu-sub title="Piutang & Hutang" icon="o-receipt-percent">
                        <x-menu-item title="Piutang" link="/piutang" />
                        <x-menu-item title="Hutang" link="/hutang" />
                    </x-menu-sub>
                @endif

                @if (in_array(auth()->user()->role_id, [1, 2, 8]))
                    <x-menu-sub title="Laporan" icon="o-chart-bar">
                        <x-menu-item title="Laporan Laba Rugi" link="/laporan-labarugi" />
                        <x-menu-item title="Laporan Neraca Saldo" link="/laporan-neraca-saldo" />
                    </x-menu-sub>
                @endif

            </x-menu>
        </x-slot:sidebar>

        {{-- Content --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{-- TOAST area --}}
    <x-toast />
</body>

</html>
