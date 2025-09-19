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
                            <x-button icon="o-power" class="btn-circle btn-ghost btn-xs" tooltip-left="logoff"
                                no-wire-navigate link="/logout" />
                        </x-slot:actions>
                    </x-list-item>

                    <x-menu-separator />
                @endif

                <x-menu-item title="Dashboard" icon="o-sparkles" link="/" />
                <x-menu-sub title="User Management" icon="o-cog-6-tooth">
                    <x-menu-item title="Users" icon="o-sparkles" link="/users" />
                    <x-menu-item title="Roles" icon="o-sparkles" link="/roles" />
                </x-menu-sub>

                <x-menu-sub title="Master Data" icon="o-cog-6-tooth">
                    <x-menu-item title="Jenis Barangs" icon="o-sparkles" link="/jenisbarangs" />
                    <x-menu-item title="Barangs" icon="o-sparkles" link="/barangs" />
                    <x-menu-item title="Satuans" icon="o-sparkles" link="/satuans" />
                </x-menu-sub>

                <x-menu-sub title="Kategoris & Clients" icon="o-cog-6-tooth">
                    <x-menu-item title="Kategoris" icon="o-sparkles" link="/kategoris" />
                    <x-menu-item title="Clients" icon="o-sparkles" link="/clients" />
                    <x-menu-item title="Transaksis" icon="o-sparkles" link="/transaksis" />
                </x-menu-sub>

                <x-menu-sub title="Transactions" icon="o-cog-6-tooth">
                    <x-menu-item title="Pembelian Telur" icon="o-sparkles" link="/telur-masuk" />
                    <x-menu-item title="Penjualan Telur" icon="o-sparkles" link="/telur-keluar" />
                    {{-- <x-menu-item title="Transaksi Pakan" icon="o-sparkles" link="/pakan" />
                    <x-menu-item title="Transaksi Obat" icon="o-sparkles" link="/obat" />
                    <x-menu-item title="Transaksi Lainnya" icon="o-sparkles" link="/lainnya" /> --}}

                    <x-menu-item title="Transaksi Kas Tunai" icon="o-sparkles" link="/tunai" />
                    <x-menu-item title="Transaksi Beban" icon="o-sparkles" link="/beban" />
                    
                    {{-- <x-menu-item title="Transaksi Bank" icon="o-sparkles" link="/bank" /> --}}
                    <x-menu-item title="Transaksi Hutang" icon="o-sparkles" link="/hutang" />
                    <x-menu-item title="Transaksi Piutang" icon="o-sparkles" link="/piutang" />

                </x-menu-sub>



            </x-menu>
        </x-slot:sidebar>

        {{-- The `$slot` goes here --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{--  TOAST area --}}
    <x-toast />
</body>

</html>
