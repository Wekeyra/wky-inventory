<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('wky.aksi.log_masuk') }} &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-[radial-gradient(60rem_40rem_at_50%_-10%,rgba(220,38,38,0.14),transparent_70%)] px-4 py-12">
    <main class="w-full max-w-sm">
        <div class="mb-4 flex justify-center">
            @include('partials.bahasa')
        </div>

        <div class="mb-6 text-center">
            <x-ikon nama="kotak-jenama" kelas="mx-auto size-12 text-merah" />
            <h1 class="mt-2 text-xl font-semibold text-white">{{ config('app.name') }}</h1>
            <p class="text-sm text-malap">{{ __('wky.app.subtajuk') }}</p>
        </div>

        <div class="kad">
            <div class="kad-badan space-y-4">
                @if ($errors->any())
                    <div class="amaran-gagal">
                        <x-ikon nama="amaran" kelas="size-5 shrink-0" />
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="mb-1 block font-medium">{{ __('wky.medan.emel') }}</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>

                    <div>
                        <label for="password" class="mb-1 block font-medium">{{ __('wky.medan.kata_laluan') }}</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <label for="ingat_saya" class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" id="ingat_saya" name="ingat_saya" value="1" class="!w-auto">
                        {{ __('wky.auth.ingat_saya') }}
                    </label>

                    <button type="submit" class="btn-utama w-full">{{ __('wky.aksi.log_masuk') }}</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
