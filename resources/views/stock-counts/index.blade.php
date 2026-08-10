@extends('layouts.app')
@section('tajuk', __('wky.kiraan.tajuk'))

@section('kandungan')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-clipboard-check me-1"></i>{{ __('wky.kiraan.tajuk_sesi') }}</span>
            <a class="btn btn-primary btn-sm" href="{{ route('stock-counts.create') }}"><i class="bi bi-plus-lg"></i> {{ __('wky.kiraan.buka_sesi_baru') }}</a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.kod') }}</th>
                        <th>{{ __('wky.medan.status') }}</th>
                        <th>{{ __('wky.kiraan.skop') }}</th>
                        <th class="text-end">{{ __('wky.medan.produk') }}</th>
                        <th>{{ __('wky.dashboard.dibuka_oleh') }}</th>
                        <th>{{ __('wky.kiraan.tarikh_buka') }}</th>
                        <th>{{ __('wky.kiraan.disahkan') }}</th>
                        <th class="text-end">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($sesi as $item)
                    <tr>
                        <td><code>{{ $item->kod }}</code></td>
                        <td><span class="badge bg-{{ $item->warnaStatus() }}">{{ $item->labelStatus() }}</span></td>
                        <td>{{ $item->category?->nama ?? __('wky.umum.semua_kategori') }}</td>
                        <td class="text-end">{{ $item->items_count }}</td>
                        <td>{{ $item->pembuka?->name ?? __('wky.umum.kosong') }}</td>
                        <td class="small text-secondary text-nowrap">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td class="small text-secondary text-nowrap">{{ $item->disahkan_pada?->format('d/m/Y H:i') ?? __('wky.umum.kosong') }}</td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('stock-counts.show', $item) }}">
                                <i class="bi bi-{{ $item->isDraf() ? 'pencil-square' : 'eye' }}"></i>
                                {{ $item->isDraf() ? __('wky.aksi.teruskan') : __('wky.aksi.lihat') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-4">
                            {!! __('wky.kiraan.tiada_sesi', ['butang' => '<strong>' . e(__('wky.kiraan.buka_sesi_baru')) . '</strong>']) !!}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($sesi->hasPages())
            <div class="card-footer">{{ $sesi->links() }}</div>
        @endif
    </div>
@endsection
