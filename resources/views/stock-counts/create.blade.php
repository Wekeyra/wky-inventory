@extends('layouts.app')
@section('tajuk', 'Buka Sesi Kiraan Stok')

@section('kandungan')
    <div class="card" style="max-width: 42rem;">
        <form method="POST" action="{{ route('stock-counts.store') }}">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="category_id">Skop kiraan</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">Semua kategori — semua produk aktif</option>
                        @foreach ($categories as $kategori)
                            <option value="{{ $kategori->id }}" @selected(old('category_id') == $kategori->id)>
                                {{ $kategori->nama }} ({{ $kategori->products_count }} produk)
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Hanya produk yang berstatus aktif akan disenaraikan dalam sesi ini.</div>
                </div>

                <div class="mb-0">
                    <label class="form-label" for="catatan">Catatan</label>
                    <textarea class="form-control" id="catatan" name="catatan" rows="3" placeholder="Cth: Kiraan suku tahun ketiga, stor utama">{{ old('catatan') }}</textarea>
                </div>

                <div class="alert alert-info small mt-3 mb-0">
                    Sistem akan menyimpan gambaran baki semasa setiap produk sebagai <strong>Kuantiti Rekod</strong>.
                    Stok <strong>tidak</strong> berubah sekarang — ia hanya dilaraskan selepas anda menekan
                    <strong>Sahkan &amp; Laraskan Stok</strong> pada langkah seterusnya.
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-clipboard-check me-1"></i>Buka Sesi</button>
                <a class="btn btn-outline-secondary" href="{{ route('stock-counts.index') }}">Batal</a>
            </div>
        </form>
    </div>
@endsection
