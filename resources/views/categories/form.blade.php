@extends('layouts.app')
@section('tajuk', $category->exists ? 'Kemas Kini Kategori' : 'Tambah Kategori')

@section('kandungan')
    <div class="card kad-stat" style="max-width: 40rem;">
        <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}">
            @csrf
            @if ($category->exists) @method('PUT') @endif

            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="kod">Kod <span class="text-danger">*</span></label>
                    <input class="form-control @error('kod') is-invalid @enderror" id="kod" name="kod" value="{{ old('kod', $category->kod) }}" required>
                    @error('kod') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="nama">Nama <span class="text-danger">*</span></label>
                    <input class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $category->nama) }}" required>
                    @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-0">
                    <label class="form-label" for="keterangan">Keterangan</label>
                    <textarea class="form-control" id="keterangan" name="keterangan" rows="3">{{ old('keterangan', $category->keterangan) }}</textarea>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button class="btn btn-primary" type="submit">{{ $category->exists ? 'Kemas Kini' : 'Simpan' }}</button>
                <a class="btn btn-outline-secondary" href="{{ route('categories.index') }}">Batal</a>
            </div>
        </form>
    </div>
@endsection
