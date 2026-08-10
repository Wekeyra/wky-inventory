@extends('layouts.app')
@section('tajuk', 'Rekod Pergerakan Stok')

@section('kandungan')
    <div class="card kad-stat" style="max-width: 40rem;">
        <form method="POST" action="{{ route('stock.store') }}">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="product_id">Produk <span class="text-danger">*</span></label>
                    <select class="form-select @error('product_id') is-invalid @enderror" id="product_id" name="product_id" required>
                        <option value="">— Pilih produk —</option>
                        @foreach ($products as $produk)
                            <option value="{{ $produk->id }}" @selected(old('product_id', $terpilih) == $produk->id)>
                                {{ $produk->nama }} ({{ $produk->sku }}) — baki {{ $produk->stok }} {{ $produk->unit }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="jenis">Jenis <span class="text-danger">*</span></label>
                        <select class="form-select @error('jenis') is-invalid @enderror" id="jenis" name="jenis" required>
                            <option value="masuk" @selected(old('jenis') === 'masuk')>Stok Masuk (tambah)</option>
                            <option value="keluar" @selected(old('jenis') === 'keluar')>Stok Keluar (tolak)</option>
                            <option value="pelarasan" @selected(old('jenis') === 'pelarasan')>Pelarasan (set jumlah tepat)</option>
                        </select>
                        @error('jenis') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="kuantiti">Kuantiti <span class="text-danger">*</span></label>
                        <input class="form-control @error('kuantiti') is-invalid @enderror" type="number" min="1" id="kuantiti" name="kuantiti" value="{{ old('kuantiti') }}" required>
                        @error('kuantiti') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="rujukan">Rujukan</label>
                    <input class="form-control" id="rujukan" name="rujukan" value="{{ old('rujukan') }}" placeholder="Cth: INV-2026-001, PO-1234">
                </div>
                <div class="mb-0">
                    <label class="form-label" for="catatan">Catatan</label>
                    <textarea class="form-control" id="catatan" name="catatan" rows="2">{{ old('catatan') }}</textarea>
                </div>

                <div class="alert alert-info small mt-3 mb-0">
                    <strong>Pelarasan</strong> menetapkan stok kepada nilai kuantiti yang dimasukkan, bukan menambah atau menolak. Guna ini selepas kiraan fizikal.
                </div>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <button class="btn btn-primary" type="submit">Rekod</button>
                <a class="btn btn-outline-secondary" href="{{ route('stock.index') }}">Batal</a>
            </div>
        </form>
    </div>
@endsection
