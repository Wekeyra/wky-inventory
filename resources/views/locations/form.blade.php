@extends('layouts.app')
@section('tajuk', $location->exists ? __('wky.lokasi.kemas_kini') : __('wky.lokasi.tambah'))

@section('kandungan')
    <form method="POST" action="{{ $location->exists ? route('locations.update', $location) : route('locations.store') }}"
          class="kad max-w-2xl">
        @csrf
        @if ($location->exists) @method('PUT') @endif

        <div class="kad-badan space-y-4">
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="kod" class="mb-1 block font-medium">{{ __('wky.medan.kod') }} <span class="text-bahaya">*</span></label>
                    <input id="kod" name="kod" value="{{ old('kod', $location->kod) }}" required @error('kod') class="medan-ralat" @enderror>
                    @error('kod') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="nama" class="mb-1 block font-medium">{{ __('wky.medan.nama') }} <span class="text-bahaya">*</span></label>
                    <input id="nama" name="nama" value="{{ old('nama', $location->nama) }}" required @error('nama') class="medan-ralat" @enderror>
                    @error('nama') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="alamat" class="mb-1 block font-medium">{{ __('wky.medan.alamat') }}</label>
                <textarea id="alamat" name="alamat" rows="3">{{ old('alamat', $location->alamat) }}</textarea>
            </div>

            <div>
                <label for="lalai" class="flex cursor-pointer items-center gap-2">
                    {{-- Lokasi lalai tidak boleh dinyahtanda terus; ia berpindah dengan menandakan lokasi lain. --}}
                    <input type="checkbox" id="lalai" name="lalai" value="1" class="!w-auto"
                           @checked(old('lalai', $location->lalai ?? false)) @disabled($location->lalai)>
                    {{ __('wky.lokasi.jadikan_lalai') }}
                </label>
                <p class="mt-1 text-xs text-malap">{{ __('wky.lokasi.nota_lalai') }}</p>
            </div>

            <label for="aktif" class="flex cursor-pointer items-center gap-2">
                <input type="checkbox" id="aktif" name="aktif" value="1" class="!w-auto" @checked(old('aktif', $location->aktif ?? true))>
                {{ __('wky.lokasi.aktif') }}
            </label>
        </div>

        <div class="kad-kaki">
            <button type="submit" class="btn-utama">{{ $location->exists ? __('wky.aksi.kemas_kini') : __('wky.aksi.simpan') }}</button>
            <a href="{{ route('locations.index') }}" class="btn-garis">{{ __('wky.aksi.batal') }}</a>
        </div>
    </form>
@endsection
