<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('wky.auth.set_tajuk') }} &middot; {{ config('app.name') }}</title>
    @include('partials.skrip-tema')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="latar-log-masuk relative min-h-screen overflow-x-hidden">
    <x-latar-log-masuk />
    <x-hiasan-3d />

    <main class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="mb-8 text-center">
            <x-logo-wky kelas="jenama-log-masuk mx-auto size-28 sm:size-32" />
            <h1 class="mt-5">
                <x-jenama-wky kelas="text-2xl sm:text-3xl" />
            </h1>
        </div>

        <div class="kad-log-masuk w-full max-w-md">
            @if ($errors->any())
                <div class="amaran-gagal mb-5">
                    <x-ikon nama="amaran" kelas="size-5 shrink-0" />
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <h2 class="mb-5 text-lg font-semibold text-teks">{{ __('wky.auth.set_tajuk') }}</h2>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf

                {{-- Token datang daripada pautan emel; emel dibawa bersamanya
                     supaya broker tahu akaun mana yang hendak ditetapkan. --}}
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-teks">
                        {{ __('wky.medan.emel') }}
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email', $email) }}"
                           required autocomplete="username">
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-teks">
                        {{ __('wky.auth.kata_laluan_baharu') }}
                    </label>
                    <x-medan-kata-laluan id="password" name="password" required autocomplete="new-password" />
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-teks">
                        {{ __('wky.auth.sahkan_kata_laluan') }}
                    </label>
                    <x-medan-kata-laluan id="password_confirmation" name="password_confirmation"
                                         required autocomplete="new-password" />
                </div>

                <button type="submit" class="btn-nyala">{{ __('wky.auth.set_kata_laluan') }}</button>
            </form>
        </div>

        <p class="mt-8 text-xs text-malap/70">
            &copy; {{ now()->format('Y') }} {{ config('app.name') }}
        </p>
    </main>
</body>
</html>
