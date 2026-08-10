@extends('layouts.app')
@section('tajuk', 'Kiraan Stok')

@section('kandungan')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-clipboard-check me-1"></i>Sesi Kiraan Stok Fizikal</span>
            <a class="btn btn-primary btn-sm" href="{{ route('stock-counts.create') }}"><i class="bi bi-plus-lg"></i> Buka Sesi Baru</a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kod</th><th>Status</th><th>Skop</th><th class="text-end">Produk</th>
                        <th>Dibuka Oleh</th><th>Tarikh Buka</th><th>Disahkan</th><th class="text-end">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($sesi as $item)
                    <tr>
                        <td><code>{{ $item->kod }}</code></td>
                        <td><span class="badge bg-{{ $item->warnaStatus() }}">{{ $item->labelStatus() }}</span></td>
                        <td>{{ $item->category?->nama ?? 'Semua kategori' }}</td>
                        <td class="text-end">{{ $item->items_count }}</td>
                        <td>{{ $item->pembuka?->name ?? '—' }}</td>
                        <td class="small text-secondary text-nowrap">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td class="small text-secondary text-nowrap">{{ $item->disahkan_pada?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('stock-counts.show', $item) }}">
                                <i class="bi bi-{{ $item->isDraf() ? 'pencil-square' : 'eye' }}"></i>
                                {{ $item->isDraf() ? 'Teruskan' : 'Lihat' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-secondary py-4">Belum ada sesi kiraan stok. Klik <strong>Buka Sesi Baru</strong> untuk mula.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($sesi->hasPages())
            <div class="card-footer">{{ $sesi->links() }}</div>
        @endif
    </div>
@endsection
