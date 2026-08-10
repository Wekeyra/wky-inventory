@extends('layouts.app')
@section('tajuk', $supplier->exists ? 'Kemas Kini Pembekal' : 'Tambah Pembekal')

@section('kandungan')
    <div class="card kad-stat" style="max-width: 48rem;">
        <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
            @csrf
            @if ($supplier->exists) @method('PUT') @endif

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="kod">Kod <span class="text-danger">*</span></label>
                        <input class="form-control @error('kod') is-invalid @enderror" id="kod" name="kod" value="{{ old('kod', $supplier->kod) }}" required>
                        @error('kod') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="nama">Nama Syarikat <span class="text-danger">*</span></label>
                        <input class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $supplier->nama) }}" required>
                        @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pegawai_perhubungan">Pegawai Perhubungan</label>
                        <input class="form-control" id="pegawai_perhubungan" name="pegawai_perhubungan" value="{{ old('pegawai_perhubungan', $supplier->pegawai_perhubungan) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="telefon">Telefon</label>
                        <input class="form-control" id="telefon" name="telefon" value="{{ old('telefon', $supplier->telefon) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="emel">Emel</label>
                        <input class="form-control @error('emel') is-invalid @enderror" type="email" id="emel" name="emel" value="{{ old('emel', $supplier->emel) }}">
                        @error('emel') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="aktif" name="aktif" value="1" @checked(old('aktif', $supplier->aktif ?? true))>
                            <label class="form-check-label" for="aktif">Pembekal aktif</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="alamat">Alamat</label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="3">{{ old('alamat', $supplier->alamat) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button class="btn btn-primary" type="submit">{{ $supplier->exists ? 'Kemas Kini' : 'Simpan' }}</button>
                <a class="btn btn-outline-secondary" href="{{ route('suppliers.index') }}">Batal</a>
            </div>
        </form>
    </div>
@endsection
