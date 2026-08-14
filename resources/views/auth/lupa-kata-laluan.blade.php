<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('wky.auth.lupa_tajuk') }} &middot; {{ config('app.name') }}</title>
    @include('partials.skrip-tema')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="latar-log-masuk relative min-h-screen overflow-x-hidden">
    <x-latar-log-masuk />
    <x-hiasan-3d />

    <main class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="mb-6 flex w-full max-w-md flex-wrap items-center justify-between gap-3">
            <a href="{{ route('login') }}" class="pautan-kembali">
                <x-ikon nama="anak-panah-kiri" kelas="size-4" />
                {{ __('wky.aksi.log_masuk') }}
            </a>

            <div class="flex items-center gap-2">
                @include('partials.bahasa')
                <x-togol-tema />
            </div>
        </div>

        <div class="mb-8 text-center">
            <x-logo-wky kelas="jenama-log-masuk mx-auto size-28 sm:size-32" />
            <h1 class="mt-5">
                <x-jenama-wky kelas="text-2xl sm:text-3xl" />
            </h1>
        </div>

        <div class="kad-log-masuk w-full max-w-md">
            @if (session('status'))
                <div class="amaran-jaya mb-5">
                    <x-ikon nama="tanda-semak" kelas="size-5 shrink-0" />
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="amaran-gagal mb-5">
                    <x-ikon nama="amaran" kelas="size-5 shrink-0" />
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <h2 class="mb-2 text-lg font-semibold text-teks">{{ __('wky.auth.lupa_tajuk') }}</h2>
            <p class="mb-5 text-sm text-malap">{{ __('wky.auth.lupa_nota') }}</p>

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-teks">
                        {{ __('wky.medan.emel') }}
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           required autofocus autocomplete="username">
                </div>

                <button type="submit" class="btn-nyala">{{ __('wky.auth.hantar_pautan') }}</button>
            </form>
        </div>

        <p class="mt-8 text-xs text-malap/70">
            &copy; {{ now()->format('Y') }} {{ config('app.name') }}
        </p>
    </main>
</body>
</html>
