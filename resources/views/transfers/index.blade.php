@extends('layouts.app')
@section('tajuk', __('wky.pindah.tajuk'))

@section('kandungan')
    <div class="kad">
        <div class="kad-kepala">
            <span class="flex items-center gap-2 font-semibold">
                <x-ikon nama="pindah" kelas="size-5 text-aksen" />
                {{ __('wky.pindah.tajuk') }}
            </span>
            <a href="{{ route('transfers.create') }}" class="btn-utama btn-kecil">
                <x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.pindah.tambah') }}
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.kod') }}</th>
                        <th>{{ __('wky.pindah.dari') }}</th>
                        <th>{{ __('wky.pindah.ke') }}</th>
                        <th class="text-right">{{ __('wky.pindah.bil_produk') }}</th>
                        <th class="text-right">{{ __('wky.lokasi.jumlah_unit') }}</th>
                        <th>{{ __('wky.medan.status') }}</th>
                        <th>{{ __('wky.medan.tarikh') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($pemindahan as $pindah)
                    <tr>
                        <td>
                            <a href="{{ route('transfers.show', $pindah) }}" class="pautan-jadual"><code>{{ $pindah->kod }}</code></a>
                        </td>
                        <td>{{ $pindah->asal?->nama }}</td>
                        <td>{{ $pindah->tujuan?->nama }}</td>
                        <td class="text-right">{{ $pindah->items_count }}</td>
                        <td class="text-right font-medium">{{ number_format((int) $pindah->jumlah_unit) }}</td>
                        <td><span class="{{ $pindah->kelasStatus() }}">{{ $pindah->labelStatus() }}</span></td>
                        <td class="whitespace-nowrap text-malap">{{ $pindah->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-malap">{{ __('wky.pindah.tiada') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($pemindahan->hasPages())
            <div class="kad-kaki penomboran block">{{ $pemindahan->links() }}</div>
        @endif
    </div>
@endsection
