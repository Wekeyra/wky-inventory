@extends('layouts.app')
@section('tajuk', __('wky.kiraan.tajuk_buka'))

@section('kandungan')
    <form method="POST" action="{{ route('stock-counts.store') }}" class="kad max-w-2xl">
        @csrf

        <div class="kad-badan space-y-4">
            {{-- Pengawal jatuh kepada gudang lalai apabila tiada lokasi
                 dihantar, jadi medan ini hilang bersama modul gudang. --}}
            @if (auth()->user()->workspace?->adaCiri('gudang'))
                <div>
                    <label for="location_id" class="mb-1 block font-medium">{{ __('wky.medan.lokasi') }} <span class="text-bahaya">*</span></label>
                    <select id="location_id" name="location_id" required @error('location_id') class="medan-ralat" @enderror>
                        @foreach ($locations as $lokasi)
                            <option value="{{ $lokasi->id }}" @selected(old('location_id', $lokasiLalai) == $lokasi->id)>{{ $lokasi->nama }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-malap">{{ __('wky.kiraan.nota_lokasi') }}</p>
                    @error('location_id') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label for="category_id" class="mb-1 block font-medium">{{ __('wky.kiraan.skop_kiraan') }}</label>
                <select id="category_id" name="category_id">
                    <option value="">{{ __('wky.kiraan.skop_semua') }}</option>
                    @foreach ($categories as $kategori)
                        <option value="{{ $kategori->id }}" @selected(old('category_id') == $kategori->id)>
                            {{ $kategori->nama }} ({{ $kategori->products_count }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-malap">{{ __('wky.kiraan.skop_nota') }}</p>
            </div>

            <div>
                <label for="catatan" class="mb-1 block font-medium">{{ __('wky.medan.catatan') }}</label>
                <textarea id="catatan" name="catatan" rows="3" placeholder="{{ __('wky.kiraan.catatan_placeholder') }}">{{ old('catatan') }}</textarea>
            </div>

            <div class="amaran-info">
                <span>
                    {!! __('wky.kiraan.nota_buka', [
                        'rekod' => '<strong>' . e(__('wky.kiraan.kuantiti_rekod')) . '</strong>',
                        'sahkan' => '<strong>' . e(__('wky.kiraan.sahkan_laraskan')) . '</strong>',
                    ]) !!}
                </span>
            </div>
        </div>

        <div class="kad-kaki">
            <button type="submit" class="btn-utama"><x-ikon nama="papan-klip" kelas="size-4" /> {{ __('wky.kiraan.buka_sesi') }}</button>
            <a href="{{ route('stock-counts.index') }}" class="btn-garis">{{ __('wky.aksi.batal') }}</a>
        </div>
    </form>
@endsection
