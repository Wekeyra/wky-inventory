@extends('layouts.app')
@section('tajuk', 'Pergerakan Stok')

@section('kandungan')
    <div class="card kad-stat">
        <div class="card-header bg-white">
            <form class="row g-2 align-items-center" method="GET">
                <div class="col-md-4">
                    <select class="form-select" name="product_id">
                        <option value="">Semua produk</option>
                        @foreach ($products as $produk)
                            <option value="{{ $produk->id }}" @selected(request('product_id') == $produk->id)>{{ $produk->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="jenis">
                        <option value="">Semua jenis</option>
                        <option value="masuk" @selected(request('jenis') === 'masuk')>Stok Masuk</option>
                        <option value="keluar" @selected(request('jenis') === 'keluar')>Stok Keluar</option>
                        <option value="pelarasan" @selected(request('jenis') === 'pelarasan')>Pelarasan</option>
                    </select>
                </div>
                <div class="col d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-funnel"></i> Tapis</button>
                    <a class="btn btn-primary" href="{{ route('stock.create') }}"><i class="bi bi-plus-lg"></i> Rekod Baru</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Tarikh</th><th>Produk</th><th>Jenis</th><th class="text-end">Kuantiti</th><th class="text-end">Sebelum</th><th class="text-end">Selepas</th><th>Rujukan</th><th>Oleh</th></tr>
                </thead>
                <tbody>
                @forelse ($movements as $gerak)
                    <tr>
                        <td class="small text-muted text-nowrap">{{ $gerak->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a class="text-decoration-none" href="{{ route('products.show', $gerak->product_id) }}">{{ $gerak->product?->nama ?? '—' }}</a>
                            <div class="small text-muted">{{ $gerak->product?->sku }}</div>
                        </td>
                        <td><span class="badge bg-{{ ['masuk' => 'success', 'keluar' => 'danger'][$gerak->jenis] ?? 'secondary' }}">{{ $gerak->labelJenis() }}</span></td>
                        <td class="text-end fw-medium">{{ $gerak->kuantiti }}</td>
                        <td class="text-end text-muted">{{ $gerak->stok_sebelum }}</td>
                        <td class="text-end fw-medium">{{ $gerak->stok_selepas }}</td>
                        <td class="small">{{ $gerak->rujukan ?? '—' }}</td>
                        <td class="small text-muted">{{ $gerak->user?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Tiada rekod pergerakan stok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($movements->hasPages())
            <div class="card-footer bg-white">{{ $movements->links() }}</div>
        @endif
    </div>
@endsection
