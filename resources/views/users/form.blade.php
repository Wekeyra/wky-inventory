@extends('layouts.app')
@section('tajuk', $user->exists ? __('wky.pengguna.kemas_kini') : __('wky.pengguna.tambah'))

@section('kandungan')
    <div class="card kad-stat" style="max-width: 40rem;">
        <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
            @csrf
            @if ($user->exists) @method('PUT') @endif

            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="name">{{ __('wky.medan.nama') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">{{ __('wky.medan.emel') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="peranan">{{ __('wky.medan.peranan') }} <span class="text-danger">*</span></label>
                    <select class="form-select" id="peranan" name="peranan" required>
                        <option value="staf" @selected(old('peranan', $user->peranan) === 'staf')>{{ __('wky.pengguna.peranan_staf') }}</option>
                        <option value="admin" @selected(old('peranan', $user->peranan) === 'admin')>{{ __('wky.pengguna.peranan_admin') }}</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">
                        {{ __('wky.medan.kata_laluan') }}
                        @if ($user->exists)
                            <span class="text-secondary small">{{ __('wky.pengguna.kata_laluan_kosong') }}</span>
                        @else
                            <span class="text-danger">*</span>
                        @endif
                    </label>
                    <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password" @required(! $user->exists)>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-0">
                    <label class="form-label" for="password_confirmation">{{ __('wky.medan.sahkan_kata_laluan') }}</label>
                    <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" @required(! $user->exists)>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button class="btn btn-primary" type="submit">{{ $user->exists ? __('wky.aksi.kemas_kini') : __('wky.aksi.simpan') }}</button>
                <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">{{ __('wky.aksi.batal') }}</a>
            </div>
        </form>
    </div>
@endsection
