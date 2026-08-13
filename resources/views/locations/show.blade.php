@extends('layouts.app')
@section('tajuk', $location->nama)

@section('kandungan')
    <div class="kad">
        <div class="kad-kepala">
            <span class="flex items-center gap-2 font-semibold">
                <x-ikon nama="gudang" kelas="size-5 text-merah" />
                <code>{{ $location->kod }}</code>
                {{ $location->nama }}
                @if ($location->lalai)
                    <span class="lencana-biru">{{ __('wky.lokasi.lalai') }}</span>
                @endif
            </span>
            <div class="flex gap-2">
                <a href="{{ route('transfers.create', ['location_id' => $location->id]) }}" class="btn-wky btn-kecil">
                    <x-ikon nama="pindah" kelas="size-4" /> {{ __('wky.pindah.tambah') }}
                </a>
                <a href="{{ route('stock.index', ['location_id' => $location->id]) }}" class="btn-garis btn-kecil">
                    {{ __('wky.lokasi.pergerakan') }}
                </a>
            </div>
        </div>

        @if ($location->alamat)
            <div class="kad-badan border-b border-bingkai text-sm text-malap">{{ $location->alamat }}</div>
        @endif

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.sku') }}</th>
                        <th>{{ __('wky.medan.produk') }}</th>
                        <th>{{ __('wky.lokasi.rak') }}</th>
                        <th class="text-right">{{ __('wky.medan.kuantiti') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($balances as $baki)
                    <tr>
                        <td><code>{{ $baki->product?->sku }}</code></td>
                        <td>
                            <a href="{{ route('products.show', $baki->product_id) }}" class="pautan-jadual">{{ $baki->product?->nama }}</a>
                        </td>
                        <td class="text-malap">{{ $baki->rak ?: __('wky.umum.kosong') }}</td>
                        <td class="text-right font-medium">{{ $baki->kuantiti }} {{ $baki->product?->unit }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-10 text-center text-malap">{{ __('wky.lokasi.tiada_stok') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($balances->hasPages())
            <div class="kad-kaki penomboran block">{{ $balances->links() }}</div>
        @endif
    </div>
@endsection
