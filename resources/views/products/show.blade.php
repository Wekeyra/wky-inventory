@extends('layouts.app')
@section('tajuk', $product->nama)

@section('kandungan')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card kad-stat">
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">{{ __('wky.medan.sku') }}</dt><dd class="col-7"><code>{{ $product->sku }}</code></dd>
                        <dt class="col-5">{{ __('wky.medan.kategori') }}</dt><dd class="col-7">{{ $product->category?->nama ?? __('wky.umum.kosong') }}</dd>
                        <dt class="col-5">{{ __('wky.medan.pembekal') }}</dt><dd class="col-7">{{ $product->supplier?->nama ?? __('wky.umum.kosong') }}</dd>
                        <dt class="col-5">{{ __('wky.medan.harga_kos') }}</dt><dd class="col-7">RM {{ number_format($product->harga_kos, 2) }}</dd>
                        <dt class="col-5">{{ __('wky.medan.harga_jual') }}</dt><dd class="col-7">RM {{ number_format($product->harga_jual, 2) }}</dd>
                        <dt class="col-5">{{ __('wky.produk.stok_semasa') }}</dt>
                        <dd class="col-7">
                            <span class="badge bg-{{ $product->stok <= $product->stok_minimum ? 'danger' : 'success' }}">
                                {{ $product->stok }} {{ $product->unit }}
                            </span>
                        </dd>
                        <dt class="col-5">{{ __('wky.medan.stok_minimum') }}</dt><dd class="col-7">{{ $product->stok_minimum }}</dd>
                        <dt class="col-5">{{ __('wky.produk.nilai_stok') }}</dt><dd class="col-7">RM {{ number_format($product->nilaiStok(), 2) }}</dd>
                        <dt class="col-5">{{ __('wky.medan.status') }}</dt>
                        <dd class="col-7"><span class="badge bg-{{ $product->aktif ? 'success' : 'secondary' }}">{{ $product->aktif ? __('wky.umum.aktif') : __('wky.umum.tidak_aktif') }}</span></dd>
                    </dl>
                    @if ($product->keterangan)
                        <hr>
                        <p class="small text-secondary mb-0">{{ $product->keterangan }}</p>
                    @endif
                </div>
                <div class="card-footer d-flex gap-2">
                    <a class="btn btn-sm btn-primary" href="{{ route('products.edit', $product) }}"><i class="bi bi-pencil"></i> {{ __('wky.aksi.kemas_kini') }}</a>
                    <a class="btn btn-sm btn-success" href="{{ route('stock.create', ['product_id' => $product->id]) }}"><i class="bi bi-arrow-left-right"></i> {{ __('wky.aksi.rekod_stok') }}</a>
                    <a class="btn btn-sm btn-outline-secondary ms-auto" href="{{ route('products.index') }}">{{ __('wky.aksi.kembali') }}</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card kad-stat">
                <div class="card-header fw-semibold"><i class="bi bi-clock-history me-1"></i>{{ __('wky.produk.sejarah_pergerakan') }}</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('wky.medan.tarikh') }}</th>
                                <th>{{ __('wky.medan.jenis') }}</th>
                                <th class="text-end">{{ __('wky.medan.kuantiti') }}</th>
                                <th class="text-end">{{ __('wky.stok.sebelum') }}</th>
                                <th class="text-end">{{ __('wky.stok.selepas') }}</th>
                                <th>{{ __('wky.medan.rujukan') }}</th>
                                <th>{{ __('wky.medan.oleh') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($movements as $gerak)
                            <tr>
                                <td class="small text-secondary">{{ $gerak->created_at->format('d/m/Y H:i') }}</td>
                                <td><span class="badge bg-{{ ['masuk' => 'success', 'keluar' => 'danger'][$gerak->jenis] ?? 'secondary' }}">{{ $gerak->labelJenis() }}</span></td>
                                <td class="text-end">{{ $gerak->kuantiti }}</td>
                                <td class="text-end text-secondary">{{ $gerak->stok_sebelum }}</td>
                                <td class="text-end fw-medium">{{ $gerak->stok_selepas }}</td>
                                <td class="small">{{ $gerak->rujukan ?? __('wky.umum.kosong') }}</td>
                                <td class="small text-secondary">{{ $gerak->user?->name ?? __('wky.umum.kosong') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-secondary py-4">{{ __('wky.dashboard.tiada_pergerakan') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($movements->hasPages())
                    <div class="card-footer">{{ $movements->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
