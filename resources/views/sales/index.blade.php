@extends('layouts.app')
@section('tajuk', __('wky.jual.tajuk'))

@section('kandungan')
    <div class="kad">
        <div class="kad-kepala">
            <span class="font-semibold">{{ __('wky.jual.tajuk') }}</span>

            <a href="{{ route('sales.create') }}" class="btn-utama">
                <x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.jual.tambah') }}
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.kod') }}</th>
                        <th>{{ __('wky.jual.pelanggan') }}</th>
                        <th>{{ __('wky.medan.lokasi') }}</th>
                        <th class="text-right">{{ __('wky.jual.bil_produk') }}</th>
                        <th class="text-right">{{ __('wky.jual.jumlah_jualan') }}</th>
                        <th class="text-right">{{ __('wky.jual.kos_barang') }}</th>
                        <th class="text-right">{{ __('wky.jual.untung_kasar') }}</th>
                        <th>{{ __('wky.medan.tarikh') }}</th>
                        <th class="text-right">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($jualan as $satu)
                    <tr>
                        <td><code>{{ $satu->kod }}</code></td>
                        <td>{{ $satu->pelanggan ?: __('wky.umum.kosong') }}</td>
                        <td class="text-malap">{{ $satu->location?->nama ?? __('wky.umum.kosong') }}</td>
                        <td class="text-right">{{ $satu->items->count() }}</td>
                        <td class="text-right whitespace-nowrap">{{ number_format($satu->jumlahJualan(), 2) }}</td>
                        <td class="text-right whitespace-nowrap text-malap">{{ number_format($satu->kosBarangDijual(), 2) }}</td>
                        <td class="text-right font-medium whitespace-nowrap">
                            {{ number_format($satu->untungKasar(), 2) }}
                            {{-- Jualan yang sebahagian barisnya tiada kos menunjukkan untung
                                 yang lebih tinggi daripada yang sebenar, jadi ia ditanda. --}}
                            @unless ($satu->kosPenuh())
                                <span class="lencana-kuning ml-1" title="{{ __('wky.jual.kos_tidak_lengkap') }}">?</span>
                            @endunless
                        </td>
                        <td class="whitespace-nowrap text-malap">{{ $satu->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="flex justify-end">
                                <a href="{{ route('sales.show', $satu) }}" class="btn-garis btn-kecil">
                                    <x-ikon nama="mata" kelas="size-4" /> {{ __('wky.aksi.lihat') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-10 text-center text-malap">{{ __('wky.jual.tiada') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($jualan->hasPages())
            <div class="kad-kaki penomboran">{{ $jualan->links() }}</div>
        @endif
    </div>
@endsection
