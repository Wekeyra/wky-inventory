@extends('layouts.app')
@section('tajuk', 'Dashboard')

@section('kandungan')
    <div class="row g-3 mb-4">
        @php
            $stat = [
                ['Jumlah Produk', number_format($jumlahProduk), 'bi-box', 'primary'],
                ['Kategori', number_format($jumlahKategori), 'bi-tags', 'success'],
                ['Pembekal', number_format($jumlahPembekal), 'bi-truck', 'info'],
                ['Nilai Stok (RM)', number_format($nilaiStok, 2), 'bi-cash-stack', 'warning'],
            ];
        @endphp
        @foreach ($stat as [$label, $nilai, $ikon, $warna])
            <div class="col-sm-6 col-xl-3">
                <div class="card kad-stat h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-{{ $warna }} bg-opacity-10 p-3 me-3">
                            <i class="bi {{ $ikon }} fs-4 text-{{ $warna }}"></i>
                        </div>
                        <div>
                            <div class="text-muted small">{{ $label }}</div>
                            <div class="fs-4 fw-semibold">{{ $nilai }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card kad-stat h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-exclamation-triangle text-danger me-1"></i>Amaran Stok Rendah</span>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('products.index', ['stok_rendah' => 1]) }}">Lihat semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Produk</th><th class="text-end">Stok</th><th class="text-end">Minimum</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($stokRendah as $produk)
                            <tr>
                                <td>
                                    <a href="{{ route('products.show', $produk) }}" class="text-decoration-none">{{ $produk->nama }}</a>
                                    <div class="small text-muted">{{ $produk->sku }}</div>
                                </td>
                                <td class="text-end"><span class="badge bg-danger">{{ $produk->stok }}</span></td>
                                <td class="text-end text-muted">{{ $produk->stok_minimum }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Tiada produk pada paras kritikal.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card kad-stat h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-clock-history me-1"></i>Pergerakan Stok Terkini</span>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('stock.index') }}">Lihat semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Produk</th><th>Jenis</th><th class="text-end">Kuantiti</th><th>Tarikh</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($pergerakanTerkini as $gerak)
                            <tr>
                                <td>{{ $gerak->product?->nama ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ ['masuk' => 'success', 'keluar' => 'danger'][$gerak->jenis] ?? 'secondary' }}">
                                        {{ $gerak->labelJenis() }}
                                    </span>
                                </td>
                                <td class="text-end">{{ $gerak->kuantiti }}</td>
                                <td class="small text-muted">{{ $gerak->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada pergerakan stok.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
