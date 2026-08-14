<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('tajuk', __('wky.nav.dashboard')) &middot; {{ config('app.name') }}</title>
    @include('partials.skrip-tema')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('kepala')
</head>
<body class="min-h-screen">
<a href="#kandungan-utama" class="pautan-langkau">{{ __('wky.umum.langkau_ke_kandungan') }}</a>

@php
    $menu = [
        ['dashboard', 'dashboard', 'dashboard', 'nav.dashboard'],
        ['products.index', 'products.*', 'kotak', 'nav.produk'],
        ['categories.index', 'categories.*', 'tag', 'nav.kategori'],
        ['suppliers.index', 'suppliers.*', 'trak', 'nav.pembekal'],
        ['locations.index', 'locations.*', 'gudang', 'nav.lokasi'],
        ['purchase-orders.index', 'purchase-orders.*', 'papan-klip', 'nav.pesanan_belian'],
        ['sales.index', 'sales.*', 'wang', 'nav.jualan'],
        ['invoice-scans.index', 'invoice-scans.*', 'imbas', 'nav.imbas_invois'],
        ['stock-counts.index', 'stock-counts.*', 'papan-klip', 'nav.kiraan_stok'],
        ['transfers.index', 'transfers.*', 'pindah', 'nav.pindah_stok'],
        ['stock.index', 'stock.*', 'anak-panah-dua-arah', 'nav.pergerakan_stok'],
        ['reports.monthly', 'reports.*', 'dokumen-carta', 'nav.laporan_bulanan'],
    ];

    if (auth()->user()->isAdmin()) {
        $menu[] = ['users.index', 'users.*', 'pengguna-ramai', 'nav.pengguna'];
    }
@endphp

<div class="flex min-h-screen">
    <aside class="bar-sisi hidden w-64 shrink-0 border-r border-bingkai bg-tinggi p-4 md:block">
        <a href="{{ route('dashboard') }}" class="mb-6 flex items-center gap-2 text-teks">
            <x-logo-w kelas="size-7" />
            <span class="min-w-0 truncate text-lg font-semibold" title="{{ auth()->user()->workspace?->nama }}">
                {{ auth()->user()->workspace?->nama ?: config('app.name') }}
            </span>
        </a>

        <nav class="flex flex-col gap-1">
            @foreach ($menu as [$laluan, $corak, $ikon, $label])
                <a href="{{ route($laluan) }}"
                   class="nav-pautan {{ request()->routeIs($corak) ? 'nav-pautan-aktif' : '' }}"
                   @if (request()->routeIs($corak)) aria-current="page" @endif>
                    <x-ikon :nama="$ikon" />
                    {{ __('wky.' . $label) }}
                </a>
            @endforeach
        </nav>
    </aside>

    <main id="kandungan-utama" class="min-w-0 flex-1 px-4 py-6 md:px-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-bingkai pb-4">
            <h1 class="text-2xl font-semibold text-teks">@yield('tajuk', __('wky.nav.dashboard'))</h1>

            <div class="flex items-center gap-2">
                @include('partials.bahasa')

                <x-togol-tema />

                <div class="relative tanpa-cetak">
                    <button type="button" class="btn-wky" data-jatuh="menu-pengguna" aria-expanded="false" aria-haspopup="true">
                        <x-ikon nama="pengguna-bulat" />
                        <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                        <x-ikon nama="anak-panah-bawah" kelas="size-4" />
                    </button>

                    <div id="menu-pengguna" class="absolute right-0 z-20 mt-1 hidden w-52 overflow-hidden rounded-lg border border-bingkai bg-tinggi shadow-xl">
                        <p class="border-b border-bingkai px-4 py-2 text-xs text-malap">
                            {{ __('wky.pengguna.' . auth()->user()->peranan) }}
                        </p>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-bahaya-terang hover:bg-permukaan">
                                <x-ikon nama="log-keluar" />
                                {{ __('wky.nav.log_keluar') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @include('partials.flash')

        @yield('kandungan')
    </main>
</div>

@include('partials.butang-pantas')

@stack('skrip')
</body>
</html>
