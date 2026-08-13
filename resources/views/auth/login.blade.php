<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('wky.aksi.log_masuk') }} &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- overflow-x-hidden sahaja supaya halaman kekal boleh diskrol pada skrin pendek. --}}
<body class="latar-log-masuk relative min-h-screen overflow-x-hidden">
    <x-latar-log-masuk />

    <main class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-10">
        {{--
            Susunan yang sama seperti halaman daftar: pautan kembali di kiri,
            penukar bahasa di kanan. Lebarnya dihadkan kepada max-w-md supaya
            kedua-duanya sebaris dengan tepi kad log masuk di bawahnya.
        --}}
        <div class="mb-6 flex w-full max-w-md flex-wrap items-center justify-between gap-3">
            <a href="{{ route('landing') }}" class="pautan-kembali">
                <x-ikon nama="anak-panah-kiri" kelas="size-4" />
                {{ __('wky.auth.kembali_utama') }}
            </a>

            @include('partials.bahasa')
        </div>

        <div class="mb-8 text-center">
            <x-logo-wky kelas="jenama-log-masuk mx-auto size-36 sm:size-44" />

            <h1 class="mt-5">
                <x-jenama-wky kelas="text-3xl sm:text-4xl" />
            </h1>
            <p class="mt-2.5 text-[0.7rem] tracking-[0.3em] text-malap uppercase sm:text-xs">
                {{ __('wky.app.subtajuk') }}
            </p>
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

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-teks">
                        {{ __('wky.medan.emel') }}
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           required autofocus autocomplete="username">
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-teks">
                        {{ __('wky.medan.kata_laluan') }}
                    </label>
                    <x-medan-kata-laluan id="password" name="password"
                                         required autocomplete="current-password" />
                </div>

                <label for="ingat_saya" class="flex cursor-pointer items-center gap-2 text-sm">
                    <input type="checkbox" id="ingat_saya" name="ingat_saya" value="1" class="!w-auto">
                    {{ __('wky.auth.ingat_saya') }}
                </label>

                <button type="submit" class="btn-logam">{{ __('wky.aksi.log_masuk') }}</button>
            </form>

            @include('partials.butang-google')

            <p class="mt-6 text-center text-sm text-malap">
                {{ __('wky.auth.belum_ada_akaun') }}
                <a href="{{ route('register') }}" class="pautan-auth">{{ __('wky.auth.daftar') }}</a>
            </p>
        </div>

        <p class="mt-8 text-xs text-malap/70">
            &copy; {{ now()->format('Y') }} {{ config('app.name') }}
        </p>
    </main>
</body>
</html>
