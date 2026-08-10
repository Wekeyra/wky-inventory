@extends('layouts.app')
@section('tajuk', __('wky.stok.tajuk'))

@section('kandungan')
    <div class="card kad-stat">
        <div class="card-header">
            <form class="row g-2 align-items-center" method="GET">
                <div class="col-md-4">
                    <select class="form-select" name="product_id">
                        <option value="">{{ __('wky.umum.semua_produk') }}</option>
                        @foreach ($products as $produk)
                            <option value="{{ $produk->id }}" @selected(request('product_id') == $produk->id)>{{ $produk->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="jenis">
                        <option value="">{{ __('wky.umum.semua_jenis') }}</option>
                        <option value="masuk" @selected(request('jenis') === 'masuk')>{{ __('wky.stok.masuk') }}</option>
                        <option value="keluar" @selected(request('jenis') === 'keluar')>{{ __('wky.stok.keluar') }}</option>
                        <option value="pelarasan" @selected(request('jenis') === 'pelarasan')>{{ __('wky.stok.pelarasan') }}</option>
                    </select>
                </div>
                <div class="col d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-funnel"></i> {{ __('wky.aksi.tapis') }}</button>
                    <a class="btn btn-primary" href="{{ route('stock.create') }}"><i class="bi bi-plus-lg"></i> {{ __('wky.aksi.rekod_baru') }}</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.tarikh') }}</th>
                        <th>{{ __('wky.medan.produk') }}</th>
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
                        <td class="small text-secondary text-nowrap">{{ $gerak->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a class="text-decoration-none" href="{{ route('products.show', $gerak->product_id) }}">{{ $gerak->product?->nama ?? __('wky.umum.kosong') }}</a>
                            <div class="small text-secondary">{{ $gerak->product?->sku }}</div>
                        </td>
                        <td><span class="badge bg-{{ ['masuk' => 'success', 'keluar' => 'danger'][$gerak->jenis] ?? 'secondary' }}">{{ $gerak->labelJenis() }}</span></td>
                        <td class="text-end fw-medium">{{ $gerak->kuantiti }}</td>
                        <td class="text-end text-secondary">{{ $gerak->stok_sebelum }}</td>
                        <td class="text-end fw-medium">{{ $gerak->stok_selepas }}</td>
                        <td class="small">{{ $gerak->rujukan ?? __('wky.umum.kosong') }}</td>
                        <td class="small text-secondary">{{ $gerak->user?->name ?? __('wky.umum.kosong') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-secondary py-4">{{ __('wky.stok.tiada_rekod') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($movements->hasPages())
            <div class="card-footer">{{ $movements->links() }}</div>
        @endif
    </div>
@endsection
