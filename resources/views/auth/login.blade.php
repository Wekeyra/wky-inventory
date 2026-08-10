<!DOCTYPE html>
<html lang="ms" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log Masuk &middot; {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/tema.css') }}" rel="stylesheet">
    <style>
        body {
            background:
                radial-gradient(60rem 40rem at 50% -10%, rgba(220, 38, 38, 0.14), transparent 70%),
                var(--wky-latar);
        }
    </style>
</head>
<body class="d-flex align-items-center py-5">
<main class="container" style="max-width: 26rem;">
    <div class="text-center mb-4">
        <i class="bi bi-box-seam fs-1" style="color: var(--wky-merah);"></i>
        <h1 class="h4 mt-2 text-white">{{ config('app.name') }}</h1>
        <p class="text-secondary small mb-0">Sistem Pengurusan Inventori</p>
    </div>

    <div class="card">
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Emel</label>
                    <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Kata Laluan</label>
                    <input class="form-control" type="password" id="password" name="password" required>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="ingat_saya" name="ingat_saya" value="1">
                    <label class="form-check-label" for="ingat_saya">Ingat saya</label>
                </div>
                <button class="btn btn-primary w-100" type="submit">Log Masuk</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
