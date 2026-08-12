<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('wky.auth.daftar') }} &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    overflow-x-hidden sahaja, bukan overflow-hidden: halaman ini lebih panjang
    daripada skrin dan mesti boleh diskrol ke bawah.
--}}
<body class="latar-log-masuk relative min-h-screen overflow-x-hidden">
    <x-latar-log-masuk />

    <main class="relative z-10 flex min-h-screen flex-col items-center px-4 py-8">
        <div class="mb-8 flex w-full max-w-5xl flex-wrap items-center justify-between gap-3">
            <a href="{{ route('login') }}" class="pautan-kembali">
                <x-ikon nama="anak-panah-kiri" kelas="size-4" />
                {{ __('wky.auth.kembali_log_masuk') }}
            </a>

            @include('partials.bahasa')
        </div>

        <div class="mb-8 text-center">
            <x-logo-wky kelas="jenama-log-masuk mx-auto size-28 sm:size-32" />

            <h1 class="mt-4 text-2xl font-bold tracking-wide text-white sm:text-3xl">
                {{ __('wky.auth.daftar_tajuk') }}
            </h1>
            <p class="mt-2">
                <x-jenama-wky kelas="text-base sm:text-lg" />
            </p>
        </div>

        <div class="kad-log-masuk w-full max-w-md">
            @if ($errors->any())
                <div class="amaran-gagal mb-5">
                    <x-ikon nama="amaran" kelas="size-5 shrink-0" />
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="nama_syarikat" class="mb-1.5 block text-sm font-medium text-teks">
                        {{ __('wky.medan.nama_syarikat') }}
                    </label>
                    <input type="text" id="nama_syarikat" name="nama_syarikat" value="{{ old('nama_syarikat') }}"
                           required autofocus autocomplete="organization">
                    <p class="mt-1.5 text-xs text-malap">{{ __('wky.auth.nota_syarikat') }}</p>
                </div>

                <div>
                    <label for="name" class="mb-1.5 block text-sm font-medium text-teks">
                        {{ __('wky.medan.nama') }}
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                           required autocomplete="name">
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-teks">
                        {{ __('wky.medan.emel') }}
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           required autocomplete="username">
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-teks">
                        {{ __('wky.medan.kata_laluan') }}
                    </label>
                    <input type="password" id="password" name="password"
                           required autocomplete="new-password">
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-teks">
                        {{ __('wky.medan.sahkan_kata_laluan') }}
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           required autocomplete="new-password">
                </div>

                <button type="submit" class="btn-logam">{{ __('wky.auth.daftar') }}</button>
            </form>

            @include('partials.butang-google')

            <p class="mt-6 text-center text-sm text-malap">
                {{ __('wky.auth.sudah_ada_akaun') }}
                <a href="{{ route('login') }}" class="pautan-auth">{{ __('wky.aksi.log_masuk') }}</a>
            </p>
        </div>

        <section class="mt-16 w-full max-w-5xl">
            <div class="mb-8 text-center">
                <h2 class="text-xl font-semibold text-white sm:text-2xl">{{ __('wky.auth.ciri_tajuk') }}</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-malap">{{ __('wky.auth.ciri_subtajuk') }}</p>
            </div>

            @php
                $ciri = [
                    ['kotak', 'produk'],
                    ['imbas', 'imbas'],
                    ['papan-klip', 'kiraan'],
                    ['anak-panah-dua-arah', 'pergerakan'],
                    ['dokumen-carta', 'laporan'],
                    ['pengguna-ramai', 'pasukan'],
                ];
            @endphp

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($ciri as [$ikon, $kunci])
                    <div class="kad-ciri">
                        <div class="ikon-ciri"><x-ikon :nama="$ikon" /></div>
                        <h3 class="mb-1.5 font-semibold text-white">{{ __('wky.auth.ciri_' . $kunci . '_tajuk') }}</h3>
                        <p class="text-sm leading-relaxed text-malap">{{ __('wky.auth.ciri_' . $kunci . '_teks') }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 text-center">
                <a href="#" class="pautan-auth text-sm" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;">
                    {{ __('wky.auth.daftar') }} &uarr;
                </a>
            </div>
        </section>

        <p class="mt-12 mb-4 text-xs text-malap/70">
            &copy; {{ now()->format('Y') }} {{ config('app.name') }}
        </p>
    </main>
</body>
</html>
