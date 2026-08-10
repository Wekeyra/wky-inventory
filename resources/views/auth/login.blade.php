<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('wky.aksi.log_masuk') }} &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="latar-log-masuk relative min-h-screen overflow-hidden">
    <x-latar-log-masuk />

    <main class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="mb-6">
            @include('partials.bahasa')
        </div>

        <div class="mb-8 text-center">
            <x-logo-wky kelas="jenama-log-masuk mx-auto size-36 sm:size-44" />

            <h1 class="mt-4 text-3xl font-bold tracking-wide text-white sm:text-4xl">
                {{ config('app.name') }}
            </h1>
            <p class="mt-1 text-sm tracking-wide text-malap">{{ __('wky.app.subtajuk') }}</p>
        </div>

        <div class="kad-log-masuk w-full max-w-md">
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
                    <input type="password" id="password" name="password"
                           required autocomplete="current-password">
                </div>

                <label for="ingat_saya" class="flex cursor-pointer items-center gap-2 text-sm">
                    <input type="checkbox" id="ingat_saya" name="ingat_saya" value="1" class="!w-auto">
                    {{ __('wky.auth.ingat_saya') }}
                </label>

                <button type="submit" class="btn-logam">{{ __('wky.aksi.log_masuk') }}</button>
            </form>
        </div>

        <p class="mt-8 text-xs text-malap/70">
            &copy; {{ now()->format('Y') }} {{ config('app.name') }}
        </p>
    </main>
</body>
</html>
