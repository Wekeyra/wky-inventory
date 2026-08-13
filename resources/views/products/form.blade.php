@extends('layouts.app')
@section('tajuk', $product->exists ? __('wky.produk.kemas_kini') : __('wky.produk.tambah'))

@section('kandungan')
    {{--
        enctype diperlukan kerana borang ini membawa gambar produk. Tanpanya
        pelayar menghantar nama fail sahaja dan medan gambar sampai kosong.
    --}}
    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
          enctype="multipart/form-data" class="grid gap-4 lg:grid-cols-3">
        @csrf
        @if ($product->exists) @method('PUT') @endif

        {{--
            Borang dihantar ke products.store, bukan ke URL borang ini, jadi
            parameter ?baris_imbasan= tidak ikut sama. Ia dibawa sebagai medan
            tersembunyi supaya controller tahu baris mana yang perlu dipautkan
            dengan produk baharu itu.
        --}}
        @if ($barisImbasan)
            <input type="hidden" name="baris_imbasan" value="{{ $barisImbasan->id }}">
        @endif

        <div class="kad kad-badan lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="sku" class="mb-1 block font-medium">{{ __('wky.medan.sku') }} <span class="text-merah">*</span></label>
                    <input id="sku" name="sku" value="{{ old('sku', $product->sku) }}" required @error('sku') class="medan-ralat" @enderror>
                    @error('sku') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="nama" class="mb-1 block font-medium">{{ __('wky.medan.nama') }} <span class="text-merah">*</span></label>
                    <input id="nama" name="nama" value="{{ old('nama', $product->nama) }}" required @error('nama') class="medan-ralat" @enderror>
                    @error('nama') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-3">
                    <label for="barcode" class="mb-1 block font-medium">{{ __('wky.medan.barcode') }}</label>
                    <div class="flex gap-2">
                        <input id="barcode" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                               inputmode="numeric" autocomplete="off" placeholder="{{ __('wky.barcode.placeholder') }}"
                               @error('barcode') class="medan-ralat" @enderror>
                        <x-imbas-barcode sasaran="barcode" />
                    </div>
                    <p class="mt-1 text-xs text-malap">{{ __('wky.barcode.nota_medan') }}</p>
                    @error('barcode') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-3">
                    <label for="keterangan" class="mb-1 block font-medium">{{ __('wky.medan.keterangan') }}</label>
                    <textarea id="keterangan" name="keterangan" rows="3">{{ old('keterangan', $product->keterangan) }}</textarea>
                </div>

                <div class="sm:col-span-3">
                    <label for="gambar" class="mb-1 block font-medium">{{ __('wky.medan.gambar') }}</label>

                    <div class="flex flex-wrap items-start gap-4">
                        @if ($product->laluan_gambar)
                            <div class="shrink-0">
                                <img src="{{ route('products.gambar', $product) }}" alt="{{ $product->nama }}"
                                     class="size-24 rounded border border-bingkai object-cover">
                                <label for="buang_gambar" class="mt-2 flex cursor-pointer items-center gap-2 text-xs text-malap">
                                    <input type="checkbox" id="buang_gambar" name="buang_gambar" value="1" class="!w-auto">
                                    {{ __('wky.produk.buang_gambar') }}
                                </label>
                            </div>
                        @endif

                        <div class="min-w-56 flex-1">
                            <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/gif,image/webp"
                                   class="file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-merah-gelap file:px-3 file:py-1.5 file:text-sm file:text-white hover:file:bg-merah"
                                   @error('gambar') class="medan-ralat" @enderror>
                            <p class="mt-1 text-xs text-malap">{{ __('wky.produk.nota_gambar') }}</p>
                            @error('gambar') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="sm:col-span-3 sm:grid sm:grid-cols-2 sm:gap-4">
                    <div>
                        <label for="category_id" class="mb-1 block font-medium">{{ __('wky.medan.kategori') }}</label>
                        <select id="category_id" name="category_id">
                            <option value="">— {{ __('wky.umum.tiada') }} —</option>
                            @foreach ($categories as $kategori)
                                <option value="{{ $kategori->id }}" @selected(old('category_id', $product->category_id) == $kategori->id)>{{ $kategori->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4 sm:mt-0">
                        <label for="supplier_id" class="mb-1 block font-medium">{{ __('wky.medan.pembekal') }}</label>
                        <select id="supplier_id" name="supplier_id">
                            <option value="">— {{ __('wky.umum.tiada') }} —</option>
                            @foreach ($suppliers as $pembekal)
                                <option value="{{ $pembekal->id }}" @selected(old('supplier_id', $product->supplier_id) == $pembekal->id)>{{ $pembekal->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="kad flex flex-col">
            <div class="kad-badan grid flex-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="harga_kos" class="mb-1 block font-medium">{{ __('wky.medan.harga_kos') }} <span class="text-merah">*</span></label>
                    <input type="number" step="0.01" min="0" id="harga_kos" name="harga_kos" value="{{ old('harga_kos', $product->harga_kos ?? '0.00') }}" required @error('harga_kos') class="medan-ralat" @enderror>
                    @error('harga_kos') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="harga_jual" class="mb-1 block font-medium">{{ __('wky.medan.harga_jual') }} <span class="text-merah">*</span></label>
                    <input type="number" step="0.01" min="0" id="harga_jual" name="harga_jual" value="{{ old('harga_jual', $product->harga_jual ?? '0.00') }}" required @error('harga_jual') class="medan-ralat" @enderror>
                    @error('harga_jual') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="unit" class="mb-1 block font-medium">{{ __('wky.medan.unit') }} <span class="text-merah">*</span></label>
                    <input id="unit" name="unit" value="{{ old('unit', $product->unit ?? 'unit') }}" required>
                </div>

                <div>
                    <label for="stok_minimum" class="mb-1 block font-medium">{{ __('wky.medan.stok_minimum') }} <span class="text-merah">*</span></label>
                    <input type="number" min="0" id="stok_minimum" name="stok_minimum" value="{{ old('stok_minimum', $product->stok_minimum ?? 0) }}" required>
                </div>

                <div class="sm:col-span-2">
                    <label for="jejak_batch" class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" id="jejak_batch" name="jejak_batch" value="1" class="!w-auto" @checked(old('jejak_batch', $product->jejak_batch ?? false))>
                        {{ __('wky.produk.jejak_batch') }}
                    </label>
                    <p class="mt-1 text-xs text-malap">{{ __('wky.produk.nota_jejak_batch') }}</p>
                </div>

                <label for="aktif" class="flex cursor-pointer items-center gap-2 sm:col-span-2">
                    <input type="checkbox" id="aktif" name="aktif" value="1" class="!w-auto" @checked(old('aktif', $product->aktif ?? true))>
                    {{ __('wky.produk.produk_aktif') }}
                </label>

                @if ($product->exists)
                    <div class="amaran-info sm:col-span-2">
                        <div>
                            <p>{{ __('wky.produk.stok_semasa') }}: <strong>{{ $product->stok }} {{ $product->unit }}</strong>.</p>
                            <p class="mt-1">
                                {!! __('wky.produk.nota_stok', [
                                    'pautan' => '<a class="underline" href="' . route('stock.create', ['product_id' => $product->id]) . '">' . e(__('wky.nav.pergerakan_stok')) . '</a>',
                                ]) !!}
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="kad-kaki">
                <button type="submit" class="btn-utama">{{ $product->exists ? __('wky.aksi.kemas_kini') : __('wky.aksi.simpan') }}</button>
                <a href="{{ route('products.index') }}" class="btn-garis">{{ __('wky.aksi.batal') }}</a>
            </div>
        </div>
    </form>
@endsection
