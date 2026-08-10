@extends('layouts.app')
@section('tajuk', $supplier->nama)

@section('kandungan')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card kad-stat">
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">{{ __('wky.medan.kod') }}</dt><dd class="col-7"><code>{{ $supplier->kod }}</code></dd>
                        <dt class="col-5">{{ __('wky.medan.pegawai_perhubungan') }}</dt><dd class="col-7">{{ $supplier->pegawai_perhubungan ?? __('wky.umum.kosong') }}</dd>
                        <dt class="col-5">{{ __('wky.medan.telefon') }}</dt><dd class="col-7">{{ $supplier->telefon ?? __('wky.umum.kosong') }}</dd>
                        <dt class="col-5">{{ __('wky.medan.emel') }}</dt><dd class="col-7">{{ $supplier->emel ?? __('wky.umum.kosong') }}</dd>
                        <dt class="col-5">{{ __('wky.medan.status') }}</dt>
                        <dd class="col-7"><span class="badge bg-{{ $supplier->aktif ? 'success' : 'secondary' }}">{{ $supplier->aktif ? __('wky.umum.aktif') : __('wky.umum.tidak_aktif') }}</span></dd>
                    </dl>
                    @if ($supplier->alamat)
                        <hr>
                        <p class="small text-secondary mb-0">{{ $supplier->alamat }}</p>
                    @endif
                </div>
                <div class="card-footer d-flex gap-2">
                    <a class="btn btn-sm btn-primary" href="{{ route('suppliers.edit', $supplier) }}"><i class="bi bi-pencil"></i> {{ __('wky.aksi.kemas_kini') }}</a>
                    <a class="btn btn-sm btn-outline-secondary ms-auto" href="{{ route('suppliers.index') }}">{{ __('wky.aksi.kembali') }}</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card kad-stat">
                <div class="card-header fw-semibold"><i class="bi bi-box me-1"></i>{{ __('wky.pembekal.produk_daripada') }}</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('wky.medan.sku') }}</th>
                                <th>{{ __('wky.medan.nama') }}</th>
                                <th class="text-end">{{ __('wky.medan.harga_kos') }}</th>
                                <th class="text-end">{{ __('wky.medan.stok') }}</th>
                            </tr>
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
                            <tr><td colspan="4" class="text-center text-secondary py-4">{{ __('wky.pembekal.tiada_produk') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
