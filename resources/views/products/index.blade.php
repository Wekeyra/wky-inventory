@extends('layouts.app')
@section('tajuk', __('wky.produk.tajuk'))

@section('kandungan')
    <div class="card kad-stat">
        <div class="card-header">
            <form class="row g-2 align-items-center" method="GET">
                <div class="col-md-4">
                    <input class="form-control" type="search" name="cari" value="{{ $cari }}" placeholder="{{ __('wky.produk.cari_placeholder') }}">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="category_id">
                        <option value="">{{ __('wky.umum.semua_kategori') }}</option>
                        @foreach ($categories as $kategori)
                            <option value="{{ $kategori->id }}" @selected(request('category_id') == $kategori->id)>{{ $kategori->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="stok_rendah" name="stok_rendah" value="1" @checked(request()->boolean('stok_rendah'))>
                        <label class="form-check-label" for="stok_rendah">{{ __('wky.produk.stok_rendah_sahaja') }}</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary" type="submit" title="{{ __('wky.aksi.cari') }}"><i class="bi bi-search"></i></button>
                    <a class="btn btn-primary" href="{{ route('products.create') }}"><i class="bi bi-plus-lg"></i> {{ __('wky.aksi.tambah') }}</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.sku') }}</th>
                        <th>{{ __('wky.medan.nama') }}</th>
                        <th>{{ __('wky.medan.kategori') }}</th>
                        <th>{{ __('wky.medan.pembekal') }}</th>
                        <th class="text-end">{{ __('wky.medan.harga_jual') }}</th>
                        <th class="text-end">{{ __('wky.medan.stok') }}</th>
                        <th class="text-end">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($products as $produk)
                    <tr>
                        <td><code>{{ $produk->sku }}</code></td>
                        <td>
                            <a class="text-decoration-none fw-medium" href="{{ route('products.show', $produk) }}">{{ $produk->nama }}</a>
                            @unless ($produk->aktif)
                                <span class="badge bg-secondary ms-1">{{ __('wky.umum.tidak_aktif') }}</span>
                            @endunless
                        </td>
                        <td>{{ $produk->category?->nama ?? __('wky.umum.kosong') }}</td>
                        <td>{{ $produk->supplier?->nama ?? __('wky.umum.kosong') }}</td>
                        <td class="text-end">RM {{ number_format($produk->harga_jual, 2) }}</td>
                        <td class="text-end">
                            <span class="badge bg-{{ $produk->stok <= $produk->stok_minimum ? 'danger' : 'success' }}">
                                {{ $produk->stok }} {{ $produk->unit }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-success" href="{{ route('stock.create', ['product_id' => $produk->id]) }}" title="{{ __('wky.aksi.rekod_stok') }}"><i class="bi bi-arrow-left-right"></i></a>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('products.edit', $produk) }}" title="{{ __('wky.aksi.kemas_kini') }}"><i class="bi bi-pencil"></i></a>
                            <form class="d-inline" method="POST" action="{{ route('products.destroy', $produk) }}" onsubmit="return confirm('{{ __('wky.produk.sahkan_padam', ['nama' => $produk->nama]) }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit" title="{{ __('wky.aksi.padam') }}"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-4">{{ __('wky.produk.tiada_dijumpai') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="card-footer">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
