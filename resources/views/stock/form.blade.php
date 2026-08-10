@extends('layouts.app')
@section('tajuk', __('wky.stok.rekod_tajuk'))

@section('kandungan')
    <form method="POST" action="{{ route('stock.store') }}" class="kad max-w-2xl">
        @csrf

        <div class="kad-badan space-y-4">
            <div>
                <label for="product_id" class="mb-1 block font-medium">{{ __('wky.medan.produk') }} <span class="text-merah">*</span></label>
                <select id="product_id" name="product_id" required @error('product_id') class="medan-ralat" @enderror>
                    <option value="">{{ __('wky.umum.pilih_produk') }}</option>
                    @foreach ($products as $produk)
                        <option value="{{ $produk->id }}" @selected(old('product_id', $terpilih) == $produk->id)>
                            {{ $produk->nama }} ({{ $produk->sku }}) — {{ __('wky.stok.baki') }} {{ $produk->stok }} {{ $produk->unit }}
                        </option>
                    @endforeach
                </select>
                @error('product_id') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="jenis" class="mb-1 block font-medium">{{ __('wky.medan.jenis') }} <span class="text-merah">*</span></label>
                    <select id="jenis" name="jenis" required @error('jenis') class="medan-ralat" @enderror>
                        <option value="masuk" @selected(old('jenis') === 'masuk')>{{ __('wky.stok.masuk_tambah') }}</option>
                        <option value="keluar" @selected(old('jenis') === 'keluar')>{{ __('wky.stok.keluar_tolak') }}</option>
                        <option value="pelarasan" @selected(old('jenis') === 'pelarasan')>{{ __('wky.stok.pelarasan_set') }}</option>
                    </select>
                    @error('jenis') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="kuantiti" class="mb-1 block font-medium">{{ __('wky.medan.kuantiti') }} <span class="text-merah">*</span></label>
                    <input type="number" min="1" id="kuantiti" name="kuantiti" value="{{ old('kuantiti') }}" required @error('kuantiti') class="medan-ralat" @enderror>
                    @error('kuantiti') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="rujukan" class="mb-1 block font-medium">{{ __('wky.medan.rujukan') }}</label>
                <input id="rujukan" name="rujukan" value="{{ old('rujukan') }}" placeholder="{{ __('wky.stok.rujukan_placeholder') }}">
            </div>

            <div>
                <label for="catatan" class="mb-1 block font-medium">{{ __('wky.medan.catatan') }}</label>
                <textarea id="catatan" name="catatan" rows="2">{{ old('catatan') }}</textarea>
            </div>

            <div class="amaran-info">
                <span>{!! __('wky.stok.nota_pelarasan', ['pelarasan' => '<strong>' . e(__('wky.stok.pelarasan')) . '</strong>']) !!}</span>
            </div>
        </div>

        <div class="kad-kaki">
            <button type="submit" class="btn-utama">{{ __('wky.aksi.rekod') }}</button>
            <a href="{{ route('stock.index') }}" class="btn-garis">{{ __('wky.aksi.batal') }}</a>
        </div>
    </form>
@endsection
