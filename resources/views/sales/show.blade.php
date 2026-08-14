@extends('layouts.app')
@section('tajuk', __('wky.jual.tajuk_papar', ['kod' => $jualan->kod]))

@section('kandungan')
    <div class="space-y-6">
        <div class="kad">
            <div class="kad-kepala">
                <span class="flex items-center gap-2 font-semibold">
                    <code>{{ $jualan->kod }}</code>
                </span>

                <button type="button" class="btn-garis btn-kecil tanpa-cetak" onclick="window.print()">
                    <x-ikon nama="pencetak" kelas="size-4" /> {{ __('wky.aksi.cetak') }}
                </button>
            </div>

            <div class="kad-badan grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="label-stat">{{ __('wky.jual.pelanggan') }}</p>
                    <p class="mt-1">{{ $jualan->pelanggan ?: __('wky.umum.kosong') }}</p>
                </div>
                <div>
                    <p class="label-stat">{{ __('wky.medan.lokasi') }}</p>
                    <p class="mt-1">{{ $jualan->location?->nama ?? __('wky.umum.kosong') }}</p>
                </div>
                <div>
                    <p class="label-stat">{{ __('wky.jual.direkod_oleh') }}</p>
                    <p class="mt-1">{{ $jualan->user?->name ?? __('wky.umum.kosong') }}</p>
                </div>
                <div>
                    <p class="label-stat">{{ __('wky.medan.tarikh') }}</p>
                    <p class="mt-1">{{ $jualan->created_at->format('d/m/Y H:i') }}</p>
                </div>

                @if ($jualan->catatan)
                    <div class="sm:col-span-2 lg:col-span-4">
                        <p class="label-stat">{{ __('wky.medan.catatan') }}</p>
                        <p class="mt-1">{{ $jualan->catatan }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="kad kad-badan">
                <p class="label-stat">{{ __('wky.jual.jumlah_jualan') }}</p>
                <p class="nilai-stat mt-1">{{ number_format($jualan->jumlahJualan(), 2) }}</p>
            </div>
            <div class="kad kad-badan">
                <p class="label-stat">{{ __('wky.jual.kos_barang') }}</p>
                <p class="nilai-stat mt-1">{{ number_format($jualan->kosBarangDijual(), 2) }}</p>
            </div>
            <div class="kad kad-badan">
                <p class="label-stat">{{ __('wky.jual.untung_kasar') }}</p>
                <p class="nilai-stat mt-1">{{ number_format($jualan->untungKasar(), 2) }}</p>
                @if ($jualan->jumlahJualan() > 0)
                    <p class="mt-1 text-xs text-malap">
                        {{ __('wky.jual.margin') }}:
                        {{ number_format($jualan->untungKasar() / $jualan->jumlahJualan() * 100, 1) }}%
                    </p>
                @endif
            </div>
        </div>

        @unless ($jualan->kosPenuh())
            <div class="amaran-gagal">
                <x-ikon nama="amaran" kelas="size-5 shrink-0" />
                <span>{{ __('wky.jual.kos_tidak_lengkap') }}</span>
            </div>
        @endunless

        <div class="kad">
            <div class="kad-kepala">
                <span class="font-semibold">{{ __('wky.jual.barang') }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="jadual">
                    <thead>
                        <tr>
                            <th>{{ __('wky.medan.produk') }}</th>
                            <th>{{ __('wky.jual.lot') }}</th>
                            <th class="text-right">{{ __('wky.medan.kuantiti') }}</th>
                            <th class="text-right">{{ __('wky.jual.harga_jual') }}</th>
                            <th class="text-right">{{ __('wky.medan.kos_seunit') }}</th>
                            <th class="text-right">{{ __('wky.jual.jumlah_jualan') }}</th>
                            <th class="text-right">{{ __('wky.jual.untung_kasar') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($jualan->items as $item)
                        <tr>
                            <td>
                                <a href="{{ route('products.show', $item->product_id) }}" class="pautan-jadual">
                                    {{ $item->product?->nama ?? __('wky.umum.kosong') }}
                                </a>
                                <p class="text-xs text-malap">{{ $item->product?->sku }}</p>
                            </td>
                            <td class="text-malap">
                                {{ $item->batch?->no_batch ?? __('wky.umum.kosong') }}
                            </td>
                            <td class="text-right">{{ $item->kuantiti }}</td>
                            <td class="text-right whitespace-nowrap">{{ number_format((float) $item->harga_jual, 2) }}</td>
                            <td class="text-right whitespace-nowrap">
                                @if ($item->kos_seunit === null)
                                    <span class="text-malap">{{ __('wky.stok.kos_tidak_direkod') }}</span>
                                @else
                                    {{ number_format((float) $item->kos_seunit, 2) }}
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">{{ number_format($item->nilaiJualan(), 2) }}</td>
                            <td class="text-right font-medium whitespace-nowrap">
                                {{ $item->untung() === null ? __('wky.umum.kosong') : number_format($item->untung(), 2) }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5">{{ __('wky.umum.jumlah') }}</td>
                            <td class="text-right whitespace-nowrap">{{ number_format($jualan->jumlahJualan(), 2) }}</td>
                            <td class="text-right whitespace-nowrap">{{ number_format($jualan->untungKasar(), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="kad-kaki block text-xs text-malap">{{ __('wky.jual.nota_kekal') }}</div>
        </div>

        <a href="{{ route('sales.index') }}" class="btn-garis tanpa-cetak">{{ __('wky.aksi.kembali') }}</a>
    </div>
@endsection
