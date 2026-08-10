@extends('layouts.app')
@section('tajuk', 'Produk')

@section('kandungan')
    <div class="card kad-stat">
        <div class="card-header">
            <form class="row g-2 align-items-center" method="GET">
                <div class="col-md-4">
                    <input class="form-control" type="search" name="cari" value="{{ $cari }}" placeholder="Cari nama atau SKU…">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="category_id">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $kategori)
                            <option value="{{ $kategori->id }}" @selected(request('category_id') == $kategori->id)>{{ $kategori->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="stok_rendah" name="stok_rendah" value="1" @checked(request()->boolean('stok_rendah'))>
                        <label class="form-check-label" for="stok_rendah">Stok rendah sahaja</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    <a class="btn btn-primary" href="{{ route('products.create') }}"><i class="bi bi-plus-lg"></i> Tambah</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>SKU</th><th>Nama</th><th>Kategori</th><th>Pembekal</th>
                        <th class="text-end">Harga Jual</th><th class="text-end">Stok</th><th class="text-end">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($products as $produk)
                    <tr>
                        <td><code>{{ $produk->sku }}</code></td>
                        <td>
                            <a class="text-decoration-none fw-medium" href="{{ route('products.show', $produk) }}">{{ $produk->nama }}</a>
                            @unless ($produk->aktif)
                                <span class="badge bg-secondary ms-1">Tidak aktif</span>
                            @endunless
                        </td>
                        <td>{{ $produk->category?->nama ?? '—' }}</td>
                        <td>{{ $produk->supplier?->nama ?? '—' }}</td>
                        <td class="text-end">RM {{ number_format($produk->harga_jual, 2) }}</td>
                        <td class="text-end">
                            <span class="badge bg-{{ $produk->stok <= $produk->stok_minimum ? 'danger' : 'success' }}">
                                {{ $produk->stok }} {{ $produk->unit }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-success" href="{{ route('stock.create', ['product_id' => $produk->id]) }}" title="Rekod stok"><i class="bi bi-arrow-left-right"></i></a>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('products.edit', $produk) }}"><i class="bi bi-pencil"></i></a>
                            <form class="d-inline" method="POST" action="{{ route('products.destroy', $produk) }}" onsubmit="return confirm('Padam produk {{ $produk->nama }}? Semua rekod pergerakan stoknya turut dipadam.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-4">Tiada produk dijumpai.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="card-footer">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
