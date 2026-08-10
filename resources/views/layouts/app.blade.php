<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('tajuk', __('wky.nav.dashboard')) &middot; {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/tema.css') }}" rel="stylesheet">
    @stack('kepala')
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar p-3">
            <a href="{{ route('dashboard') }}" class="jenama d-flex align-items-center mb-4">
                <i class="bi bi-box-seam fs-4 me-2"></i>
                <span class="fs-5 fw-semibold">{{ config('app.name') }}</span>
            </a>
            <ul class="nav nav-pills flex-column gap-1">
                <li><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>{{ __('wky.nav.dashboard') }}</a></li>
                <li><a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><i class="bi bi-box me-2"></i>{{ __('wky.nav.produk') }}</a></li>
                <li><a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}"><i class="bi bi-tags me-2"></i>{{ __('wky.nav.kategori') }}</a></li>
                <li><a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}"><i class="bi bi-truck me-2"></i>{{ __('wky.nav.pembekal') }}</a></li>
                <li><a class="nav-link {{ request()->routeIs('stock-counts.*') ? 'active' : '' }}" href="{{ route('stock-counts.index') }}"><i class="bi bi-clipboard-check me-2"></i>{{ __('wky.nav.kiraan_stok') }}</a></li>
                <li><a class="nav-link {{ request()->routeIs('stock.*') ? 'active' : '' }}" href="{{ route('stock.index') }}"><i class="bi bi-arrow-left-right me-2"></i>{{ __('wky.nav.pergerakan_stok') }}</a></li>
                <li><a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.monthly') }}"><i class="bi bi-file-earmark-bar-graph me-2"></i>{{ __('wky.nav.laporan_bulanan') }}</a></li>
                @if (auth()->user()->isAdmin())
                    <li><a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-people me-2"></i>{{ __('wky.nav.pengguna') }}</a></li>
                @endif
            </ul>
        </nav>

        <main class="col-md-9 col-lg-10 px-md-4 py-4">
            <div class="tajuk-halaman d-flex flex-wrap gap-2 justify-content-between align-items-center pb-3 mb-4">
                <h1 class="h3 mb-0 text-white">@yield('tajuk', __('wky.nav.dashboard'))</h1>
                <div class="d-flex align-items-center gap-2">
                    @include('partials.bahasa')

                    <div class="dropdown tanpa-cetak">
                        <button class="btn btn-wky dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text small text-secondary">{{ __('wky.pengguna.' . auth()->user()->peranan) }}</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-1"></i>{{ __('wky.nav.log_keluar') }}</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            @include('partials.flash')

            @yield('kandungan')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('skrip')
</body>
</html>
