@extends('layouts.app')
@section('tajuk', $product->exists ? __('wky.produk.kemas_kini') : __('wky.produk.tambah'))

@section('kandungan')
    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}">
        @csrf
        @if ($product->exists) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card kad-stat">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="sku">{{ __('wky.medan.sku') }} <span class="text-danger">*</span></label>
                                <input class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" required>
                                @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="nama">{{ __('wky.medan.nama') }} <span class="text-danger">*</span></label>
                                <input class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $product->nama) }}" required>
                                @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="keterangan">{{ __('wky.medan.keterangan') }}</label>
                                <textarea class="form-control" id="keterangan" name="keterangan" rows="3">{{ old('keterangan', $product->keterangan) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="category_id">{{ __('wky.medan.kategori') }}</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">— {{ __('wky.umum.tiada') }} —</option>
                                    @foreach ($categories as $kategori)
                                        <option value="{{ $kategori->id }}" @selected(old('category_id', $product->category_id) == $kategori->id)>{{ $kategori->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="supplier_id">{{ __('wky.medan.pembekal') }}</label>
                                <select class="form-select" id="supplier_id" name="supplier_id">
                                    <option value="">— {{ __('wky.umum.tiada') }} —</option>
                                    @foreach ($suppliers as $pembekal)
                                        <option value="{{ $pembekal->id }}" @selected(old('supplier_id', $product->supplier_id) == $pembekal->id)>{{ $pembekal->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card kad-stat">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label" for="harga_kos">{{ __('wky.medan.harga_kos') }} <span class="text-danger">*</span></label>
                                <input class="form-control @error('harga_kos') is-invalid @enderror" type="number" step="0.01" min="0" id="harga_kos" name="harga_kos" value="{{ old('harga_kos', $product->harga_kos ?? '0.00') }}" required>
                                @error('harga_kos') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="harga_jual">{{ __('wky.medan.harga_jual') }} <span class="text-danger">*</span></label>
                                <input class="form-control @error('harga_jual') is-invalid @enderror" type="number" step="0.01" min="0" id="harga_jual" name="harga_jual" value="{{ old('harga_jual', $product->harga_jual ?? '0.00') }}" required>
                                @error('harga_jual') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="unit">{{ __('wky.medan.unit') }} <span class="text-danger">*</span></label>
                                <input class="form-control" id="unit" name="unit" value="{{ old('unit', $product->unit ?? 'unit') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="stok_minimum">{{ __('wky.medan.stok_minimum') }} <span class="text-danger">*</span></label>
                                <input class="form-control" type="number" min="0" id="stok_minimum" name="stok_minimum" value="{{ old('stok_minimum', $product->stok_minimum ?? 0) }}" required>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="aktif" name="aktif" value="1" @checked(old('aktif', $product->aktif ?? true))>
                                    <label class="form-check-label" for="aktif">{{ __('wky.produk.produk_aktif') }}</label>
                                </div>
                            </div>
                            @if ($product->exists)
                                <div class="col-12">
                                    <div class="alert alert-info small mb-0">
                                        {{ __('wky.produk.stok_semasa') }}: <strong>{{ $product->stok }} {{ $product->unit }}</strong>.<br>
                                        {!! __('wky.produk.nota_stok', [
                                            'pautan' => '<a href="' . route('stock.create', ['product_id' => $product->id]) . '">' . e(__('wky.nav.pergerakan_stok')) . '</a>',
                                        ]) !!}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer d-flex gap-2">
                        <button class="btn btn-primary" type="submit">{{ $product->exists ? __('wky.aksi.kemas_kini') : __('wky.aksi.simpan') }}</button>
                        <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">{{ __('wky.aksi.batal') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
