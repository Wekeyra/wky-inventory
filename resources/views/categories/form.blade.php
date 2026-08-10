@extends('layouts.app')
@section('tajuk', $category->exists ? __('wky.kategori.kemas_kini') : __('wky.kategori.tambah'))

@section('kandungan')
    <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}"
          class="kad max-w-2xl">
        @csrf
        @if ($category->exists) @method('PUT') @endif

        <div class="kad-badan space-y-4">
            <div>
                <label for="kod" class="mb-1 block font-medium">{{ __('wky.medan.kod') }} <span class="text-merah">*</span></label>
                <input id="kod" name="kod" value="{{ old('kod', $category->kod) }}" required @error('kod') class="medan-ralat" @enderror>
                @error('kod') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="nama" class="mb-1 block font-medium">{{ __('wky.medan.nama') }} <span class="text-merah">*</span></label>
                <input id="nama" name="nama" value="{{ old('nama', $category->nama) }}" required @error('nama') class="medan-ralat" @enderror>
                @error('nama') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="keterangan" class="mb-1 block font-medium">{{ __('wky.medan.keterangan') }}</label>
                <textarea id="keterangan" name="keterangan" rows="3">{{ old('keterangan', $category->keterangan) }}</textarea>
            </div>
        </div>

        <div class="kad-kaki">
            <button type="submit" class="btn-utama">{{ $category->exists ? __('wky.aksi.kemas_kini') : __('wky.aksi.simpan') }}</button>
            <a href="{{ route('categories.index') }}" class="btn-garis">{{ __('wky.aksi.batal') }}</a>
        </div>
    </form>
@endsection
