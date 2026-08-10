@extends('layouts.app')
@section('tajuk', $user->exists ? 'Kemas Kini Pengguna' : 'Tambah Pengguna')

@section('kandungan')
    <div class="card kad-stat" style="max-width: 40rem;">
        <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
            @csrf
            @if ($user->exists) @method('PUT') @endif

            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="name">Nama <span class="text-danger">*</span></label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Emel <span class="text-danger">*</span></label>
                    <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="peranan">Peranan <span class="text-danger">*</span></label>
                    <select class="form-select" id="peranan" name="peranan" required>
                        <option value="staf" @selected(old('peranan', $user->peranan) === 'staf')>Staf — akses produk, kategori, pembekal, stok</option>
                        <option value="admin" @selected(old('peranan', $user->peranan) === 'admin')>Admin — akses penuh termasuk pengurusan pengguna</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">
                        Kata Laluan {!! $user->exists ? '<span class="text-muted small">(biar kosong jika tidak mahu tukar)</span>' : '<span class="text-danger">*</span>' !!}
                    </label>
                    <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password" @required(! $user->exists)>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-0">
                    <label class="form-label" for="password_confirmation">Sahkan Kata Laluan</label>
                    <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" @required(! $user->exists)>
                </div>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <button class="btn btn-primary" type="submit">{{ $user->exists ? 'Kemas Kini' : 'Simpan' }}</button>
                <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Batal</a>
            </div>
        </form>
    </div>
@endsection
