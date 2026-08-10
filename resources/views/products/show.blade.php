@extends('layouts.app')
@section('tajuk', $product->nama)

@section('kandungan')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card kad-stat">
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">SKU</dt><dd class="col-7"><code>{{ $product->sku }}</code></dd>
                        <dt class="col-5">Kategori</dt><dd class="col-7">{{ $product->category?->nama ?? '—' }}</dd>
                        <dt class="col-5">Pembekal</dt><dd class="col-7">{{ $product->supplier?->nama ?? '—' }}</dd>
                        <dt class="col-5">Harga Kos</dt><dd class="col-7">RM {{ number_format($product->harga_kos, 2) }}</dd>
                        <dt class="col-5">Harga Jual</dt><dd class="col-7">RM {{ number_format($product->harga_jual, 2) }}</dd>
                        <dt class="col-5">Stok Semasa</dt>
                        <dd class="col-7">
                            <span class="badge bg-{{ $product->stok <= $product->stok_minimum ? 'danger' : 'success' }}">
                                {{ $product->stok }} {{ $product->unit }}
                            </span>
                        </dd>
                        <dt class="col-5">Stok Minimum</dt><dd class="col-7">{{ $product->stok_minimum }}</dd>
                        <dt class="col-5">Nilai Stok</dt><dd class="col-7">RM {{ number_format($product->nilaiStok(), 2) }}</dd>
                        <dt class="col-5">Status</dt>
                        <dd class="col-7"><span class="badge bg-{{ $product->aktif ? 'success' : 'secondary' }}">{{ $product->aktif ? 'Aktif' : 'Tidak aktif' }}</span></dd>
                    </dl>
                    @if ($product->keterangan)
                        <hr>
                        <p class="small text-muted mb-0">{{ $product->keterangan }}</p>
                    @endif
                </div>
                <div class="card-footer bg-white d-flex gap-2">
                    <a class="btn btn-sm btn-primary" href="{{ route('products.edit', $product) }}"><i class="bi bi-pencil"></i> Kemas Kini</a>
                    <a class="btn btn-sm btn-success" href="{{ route('stock.create', ['product_id' => $product->id]) }}"><i class="bi bi-arrow-left-right"></i> Rekod Stok</a>
                    <a class="btn btn-sm btn-outline-secondary ms-auto" href="{{ route('products.index') }}">Kembali</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card kad-stat">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-1"></i>Sejarah Pergerakan Stok</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Tarikh</th><th>Jenis</th><th class="text-end">Kuantiti</th><th class="text-end">Sebelum</th><th class="text-end">Selepas</th><th>Rujukan</th><th>Oleh</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($movements as $gerak)
                            <tr>
                                <td class="small text-muted">{{ $gerak->created_at->format('d/m/Y H:i') }}</td>
                                <td><span class="badge bg-{{ ['masuk' => 'success', 'keluar' => 'danger'][$gerak->jenis] ?? 'secondary' }}">{{ $gerak->labelJenis() }}</span></td>
                                <td class="text-end">{{ $gerak->kuantiti }}</td>
                                <td class="text-end text-muted">{{ $gerak->stok_sebelum }}</td>
                                <td class="text-end fw-medium">{{ $gerak->stok_selepas }}</td>
                                <td class="small">{{ $gerak->rujukan ?? '—' }}</td>
                                <td class="small text-muted">{{ $gerak->user?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pergerakan stok.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($movements->hasPages())
                    <div class="card-footer bg-white">{{ $movements->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
