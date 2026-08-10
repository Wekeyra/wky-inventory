@extends('layouts.app')
@section('tajuk', $supplier->nama)

@section('kandungan')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card kad-stat">
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Kod</dt><dd class="col-7"><code>{{ $supplier->kod }}</code></dd>
                        <dt class="col-5">Pegawai</dt><dd class="col-7">{{ $supplier->pegawai_perhubungan ?? '—' }}</dd>
                        <dt class="col-5">Telefon</dt><dd class="col-7">{{ $supplier->telefon ?? '—' }}</dd>
                        <dt class="col-5">Emel</dt><dd class="col-7">{{ $supplier->emel ?? '—' }}</dd>
                        <dt class="col-5">Status</dt>
                        <dd class="col-7"><span class="badge bg-{{ $supplier->aktif ? 'success' : 'secondary' }}">{{ $supplier->aktif ? 'Aktif' : 'Tidak aktif' }}</span></dd>
                    </dl>
                    @if ($supplier->alamat)
                        <hr>
                        <p class="small text-secondary mb-0">{{ $supplier->alamat }}</p>
                    @endif
                </div>
                <div class="card-footer d-flex gap-2">
                    <a class="btn btn-sm btn-primary" href="{{ route('suppliers.edit', $supplier) }}"><i class="bi bi-pencil"></i> Kemas Kini</a>
                    <a class="btn btn-sm btn-outline-secondary ms-auto" href="{{ route('suppliers.index') }}">Kembali</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card kad-stat">
                <div class="card-header fw-semibold"><i class="bi bi-box me-1"></i>Produk daripada Pembekal Ini</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr><th>SKU</th><th>Nama</th><th class="text-end">Harga Kos</th><th class="text-end">Stok</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($supplier->products as $produk)
                            <tr>
                                <td><code>{{ $produk->sku }}</code></td>
                                <td><a class="text-decoration-none" href="{{ route('products.show', $produk) }}">{{ $produk->nama }}</a></td>
                                <td class="text-end">RM {{ number_format($produk->harga_kos, 2) }}</td>
                                <td class="text-end">{{ $produk->stok }} {{ $produk->unit }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-secondary py-4">Tiada produk dikaitkan dengan pembekal ini.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
