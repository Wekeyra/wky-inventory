@extends('layouts.app')
@section('tajuk', $supplier->exists ? __('wky.pembekal.kemas_kini') : __('wky.pembekal.tambah'))

@section('kandungan')
    <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}"
          class="kad max-w-3xl">
        @csrf
        @if ($supplier->exists) @method('PUT') @endif

        <div class="kad-badan grid gap-4 sm:grid-cols-3">
            <div>
                <label for="kod" class="mb-1 block font-medium">{{ __('wky.medan.kod') }} <span class="text-merah">*</span></label>
                <input id="kod" name="kod" value="{{ old('kod', $supplier->kod) }}" required @error('kod') class="medan-ralat" @enderror>
                @error('kod') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="nama" class="mb-1 block font-medium">{{ __('wky.medan.nama_syarikat') }} <span class="text-merah">*</span></label>
                <input id="nama" name="nama" value="{{ old('nama', $supplier->nama) }}" required @error('nama') class="medan-ralat" @enderror>
                @error('nama') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="pegawai_perhubungan" class="mb-1 block font-medium">{{ __('wky.medan.pegawai_perhubungan') }}</label>
                <input id="pegawai_perhubungan" name="pegawai_perhubungan" value="{{ old('pegawai_perhubungan', $supplier->pegawai_perhubungan) }}">
            </div>

            <div>
                <label for="telefon" class="mb-1 block font-medium">{{ __('wky.medan.telefon') }}</label>
                <input id="telefon" name="telefon" value="{{ old('telefon', $supplier->telefon) }}">
            </div>

            <div class="sm:col-span-2">
                <label for="emel" class="mb-1 block font-medium">{{ __('wky.medan.emel') }}</label>
                <input type="email" id="emel" name="emel" value="{{ old('emel', $supplier->emel) }}" @error('emel') class="medan-ralat" @enderror>
                @error('emel') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            <label for="aktif" class="flex cursor-pointer items-center gap-2 sm:self-end sm:pb-2">
                <input type="checkbox" id="aktif" name="aktif" value="1" class="!w-auto" @checked(old('aktif', $supplier->aktif ?? true))>
                {{ __('wky.pembekal.pembekal_aktif') }}
            </label>

            <div class="sm:col-span-3">
                <label for="alamat" class="mb-1 block font-medium">{{ __('wky.medan.alamat') }}</label>
                <textarea id="alamat" name="alamat" rows="3">{{ old('alamat', $supplier->alamat) }}</textarea>
            </div>
        </div>

        <div class="kad-kaki">
            <button type="submit" class="btn-utama">{{ $supplier->exists ? __('wky.aksi.kemas_kini') : __('wky.aksi.simpan') }}</button>
            <a href="{{ route('suppliers.index') }}" class="btn-garis">{{ __('wky.aksi.batal') }}</a>
        </div>
    </form>
@endsection
