@extends('layouts.app')
@section('tajuk', __('wky.kiraan.tajuk_buka'))

@section('kandungan')
    <div class="card" style="max-width: 42rem;">
        <form method="POST" action="{{ route('stock-counts.store') }}">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="category_id">{{ __('wky.kiraan.skop_kiraan') }}</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">{{ __('wky.kiraan.skop_semua') }}</option>
                        @foreach ($categories as $kategori)
                            <option value="{{ $kategori->id }}" @selected(old('category_id') == $kategori->id)>
                                {{ $kategori->nama }} ({{ $kategori->products_count }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">{{ __('wky.kiraan.skop_nota') }}</div>
                </div>

                <div class="mb-0">
                    <label class="form-label" for="catatan">{{ __('wky.medan.catatan') }}</label>
                    <textarea class="form-control" id="catatan" name="catatan" rows="3" placeholder="{{ __('wky.kiraan.catatan_placeholder') }}">{{ old('catatan') }}</textarea>
                </div>

                <div class="alert alert-info small mt-3 mb-0">
                    {!! __('wky.kiraan.nota_buka', [
                        'rekod' => '<strong>' . e(__('wky.kiraan.kuantiti_rekod')) . '</strong>',
                        'sahkan' => '<strong>' . e(__('wky.kiraan.sahkan_laraskan')) . '</strong>',
                    ]) !!}
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-clipboard-check me-1"></i>{{ __('wky.kiraan.buka_sesi') }}</button>
                <a class="btn btn-outline-secondary" href="{{ route('stock-counts.index') }}">{{ __('wky.aksi.batal') }}</a>
            </div>
        </form>
    </div>
@endsection
