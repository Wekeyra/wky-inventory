<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('tajuk', 'Dashboard') &middot; {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f6f8; }
        .sidebar { min-height: 100vh; background: #1f2937; }
        .sidebar .nav-link { color: #cbd5e1; border-radius: .375rem; }
        .sidebar .nav-link:hover { background: #374151; color: #fff; }
        .sidebar .nav-link.active { background: #2563eb; color: #fff; }
        .kad-stat { border: 0; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar p-3">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center mb-4 text-white text-decoration-none">
                <i class="bi bi-box-seam fs-4 me-2"></i>
                <span class="fs-5 fw-semibold">{{ config('app.name') }}</span>
            </a>
            <ul class="nav nav-pills flex-column gap-1">
                <li><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                <li><a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><i class="bi bi-box me-2"></i>Produk</a></li>
                <li><a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}"><i class="bi bi-tags me-2"></i>Kategori</a></li>
                <li><a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}"><i class="bi bi-truck me-2"></i>Pembekal</a></li>
                <li><a class="nav-link {{ request()->routeIs('stock.*') ? 'active' : '' }}" href="{{ route('stock.index') }}"><i class="bi bi-arrow-left-right me-2"></i>Pergerakan Stok</a></li>
                @if (auth()->user()->isAdmin())
                    <li><a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-people me-2"></i>Pengguna</a></li>
                @endif
            </ul>
        </nav>

        <main class="col-md-9 col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <h1 class="h3 mb-0">@yield('tajuk', 'Dashboard')</h1>
                <div class="dropdown">
                    <button class="btn btn-light border dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-muted">{{ ucfirst(auth()->user()->peranan) }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-1"></i>Log Keluar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>

            @include('partials.flash')

            @yield('kandungan')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
