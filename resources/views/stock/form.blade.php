@extends('layouts.app')
@section('tajuk', __('wky.stok.rekod_tajuk'))

@section('kandungan')
    <div class="card kad-stat" style="max-width: 40rem;">
        <form method="POST" action="{{ route('stock.store') }}">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="product_id">{{ __('wky.medan.produk') }} <span class="text-danger">*</span></label>
                    <select class="form-select @error('product_id') is-invalid @enderror" id="product_id" name="product_id" required>
                        <option value="">{{ __('wky.umum.pilih_produk') }}</option>
                        @foreach ($products as $produk)
                            <option value="{{ $produk->id }}" @selected(old('product_id', $terpilih) == $produk->id)>
                                {{ $produk->nama }} ({{ $produk->sku }}) — {{ __('wky.stok.baki') }} {{ $produk->stok }} {{ $produk->unit }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="jenis">{{ __('wky.medan.jenis') }} <span class="text-danger">*</span></label>
                        <select class="form-select @error('jenis') is-invalid @enderror" id="jenis" name="jenis" required>
                            <option value="masuk" @selected(old('jenis') === 'masuk')>{{ __('wky.stok.masuk_tambah') }}</option>
                            <option value="keluar" @selected(old('jenis') === 'keluar')>{{ __('wky.stok.keluar_tolak') }}</option>
                            <option value="pelarasan" @selected(old('jenis') === 'pelarasan')>{{ __('wky.stok.pelarasan_set') }}</option>
                        </select>
                        @error('jenis') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="kuantiti">{{ __('wky.medan.kuantiti') }} <span class="text-danger">*</span></label>
                        <input class="form-control @error('kuantiti') is-invalid @enderror" type="number" min="1" id="kuantiti" name="kuantiti" value="{{ old('kuantiti') }}" required>
                        @error('kuantiti') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="rujukan">{{ __('wky.medan.rujukan') }}</label>
                    <input class="form-control" id="rujukan" name="rujukan" value="{{ old('rujukan') }}" placeholder="{{ __('wky.stok.rujukan_placeholder') }}">
                </div>
                <div class="mb-0">
                    <label class="form-label" for="catatan">{{ __('wky.medan.catatan') }}</label>
                    <textarea class="form-control" id="catatan" name="catatan" rows="2">{{ old('catatan') }}</textarea>
                </div>

                <div class="alert alert-info small mt-3 mb-0">
                    {!! __('wky.stok.nota_pelarasan', ['pelarasan' => '<strong>' . e(__('wky.stok.pelarasan')) . '</strong>']) !!}
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button class="btn btn-primary" type="submit">{{ __('wky.aksi.rekod') }}</button>
                <a class="btn btn-outline-secondary" href="{{ route('stock.index') }}">{{ __('wky.aksi.batal') }}</a>
            </div>
        </form>
    </div>
@endsection
