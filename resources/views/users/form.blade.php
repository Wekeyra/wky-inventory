@extends('layouts.app')
@section('tajuk', $user->exists ? __('wky.pengguna.kemas_kini') : __('wky.pengguna.tambah'))

@section('kandungan')
    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}"
          class="kad max-w-2xl">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        @php
            // Admin tidak boleh menurunkan peranan akaunnya sendiri.
            $sendiri = $user->exists && $user->is(auth()->user());
        @endphp

        <div class="kad-badan space-y-4">
            @if ($sendiri)
                <div class="amaran-info">
                    <x-ikon nama="amaran" kelas="size-5 shrink-0" />
                    <span>{{ __('wky.pengguna.akaun_sendiri_terkunci') }}</span>
                </div>
            @endif

            <div>
                <label for="name" class="mb-1 block font-medium">{{ __('wky.medan.nama') }} <span class="text-merah">*</span></label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required @error('name') class="medan-ralat" @enderror>
                @error('name') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="mb-1 block font-medium">{{ __('wky.medan.emel') }} <span class="text-merah">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required @error('email') class="medan-ralat" @enderror>
                @error('email') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="peranan" class="mb-1 block font-medium">{{ __('wky.medan.peranan') }} <span class="text-merah">*</span></label>
                <select id="peranan" name="peranan" required @disabled($sendiri)>
                    <option value="staf" @selected(old('peranan', $user->peranan) === 'staf')>{{ __('wky.pengguna.peranan_staf') }}</option>
                    <option value="admin" @selected(old('peranan', $user->peranan) === 'admin')>{{ __('wky.pengguna.peranan_admin') }}</option>
                </select>
            </div>

            {{-- Medan yang dilumpuhkan tidak dihantar, jadi nilai sedia ada disertakan di sini. --}}
            @if ($sendiri)
                <input type="hidden" name="peranan" value="{{ $user->peranan }}">
            @endif

            <div>
                <label for="password" class="mb-1 block font-medium">
                    {{ __('wky.medan.kata_laluan') }}
                    @if ($user->exists && $user->google_id && blank($user->password))
                        <span class="text-xs font-normal text-malap">{{ __('wky.pengguna.kata_laluan_google') }}</span>
                    @elseif ($user->exists)
                        <span class="text-xs font-normal text-malap">{{ __('wky.pengguna.kata_laluan_kosong') }}</span>
                    @else
                        <span class="text-merah">*</span>
                    @endif
                </label>
                <input type="password" id="password" name="password" @required(! $user->exists) @error('password') class="medan-ralat" @enderror>
                @error('password') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block font-medium">{{ __('wky.medan.sahkan_kata_laluan') }}</label>
                <input type="password" id="password_confirmation" name="password_confirmation" @required(! $user->exists)>
            </div>
        </div>

        <div class="kad-kaki">
            <button type="submit" class="btn-utama">{{ $user->exists ? __('wky.aksi.kemas_kini') : __('wky.aksi.simpan') }}</button>
            <a href="{{ route('users.index') }}" class="btn-garis">{{ __('wky.aksi.batal') }}</a>
        </div>
    </form>
@endsection
