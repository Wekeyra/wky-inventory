@extends('layouts.app')
@section('tajuk', __('wky.stok.tajuk'))

@section('kandungan')
    <div class="kad">
        <div class="kad-kepala">
            <form method="GET" class="grid w-full gap-2 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto]">
                <select name="product_id">
                    <option value="">{{ __('wky.umum.semua_produk') }}</option>
                    @foreach ($products as $produk)
                        <option value="{{ $produk->id }}" @selected(request('product_id') == $produk->id)>{{ $produk->nama }}</option>
                    @endforeach
                </select>

                <select name="jenis">
                    <option value="">{{ __('wky.umum.semua_jenis') }}</option>
                    <option value="masuk" @selected(request('jenis') === 'masuk')>{{ __('wky.stok.masuk') }}</option>
                    <option value="keluar" @selected(request('jenis') === 'keluar')>{{ __('wky.stok.keluar') }}</option>
                    <option value="pelarasan" @selected(request('jenis') === 'pelarasan')>{{ __('wky.stok.pelarasan') }}</option>
                    <option value="pindah" @selected(request('jenis') === 'pindah')>{{ __('wky.stok.pindah') }}</option>
                </select>

                <select name="location_id">
                    <option value="">{{ __('wky.lokasi.semua') }}</option>
                    @foreach ($locations as $lokasi)
                        <option value="{{ $lokasi->id }}" @selected(request('location_id') == $lokasi->id)>{{ $lokasi->nama }}</option>
                    @endforeach
                </select>

                <select name="sebab">
                    <option value="">{{ __('wky.stok.semua_sebab') }}</option>
                    {{-- Sebab yang muncul di bawah lebih daripada satu jenis disatukan; penapis ini tidak mengambil kira jenis. --}}
                    @foreach (collect($sebabPilihan)->collapse() as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('sebab') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button type="submit" class="btn-garis"><x-ikon nama="penapis" kelas="size-4" /> {{ __('wky.aksi.tapis') }}</button>
                    <a href="{{ route('stock.create') }}" class="btn-utama"><x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.aksi.rekod_baru') }}</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.tarikh') }}</th>
                        <th>{{ __('wky.medan.produk') }}</th>
                        <th>{{ __('wky.medan.jenis') }}</th>
                        <th>{{ __('wky.medan.lokasi') }}</th>
                        <th>{{ __('wky.medan.sebab') }}</th>
                        <th class="text-right">{{ __('wky.medan.kuantiti') }}</th>
                        <th class="text-right">{{ __('wky.medan.kos_seunit') }}</th>
                        <th class="text-right">{{ __('wky.stok.sebelum') }}</th>
                        <th class="text-right">{{ __('wky.stok.selepas') }}</th>
                        <th>{{ __('wky.medan.rujukan') }}</th>
                        <th>{{ __('wky.medan.oleh') }}</th>
                        <th class="text-right">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($movements as $gerak)
                    <tr>
                        <td class="whitespace-nowrap text-malap">{{ $gerak->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('products.show', $gerak->product_id) }}" class="pautan-jadual">{{ $gerak->product?->nama ?? __('wky.umum.kosong') }}</a>
                            <p class="text-xs text-malap">{{ $gerak->product?->sku }}</p>
                        </td>
                        <td><span class="{{ $gerak->kelasJenis() }}">{{ $gerak->labelJenis() }}</span></td>
                        <td class="text-malap">
                            @if ($gerak->isPindah())
                                {{ $gerak->location?->nama }} → {{ $gerak->tujuan?->nama }}
                            @else
                                {{ $gerak->location?->nama ?? __('wky.umum.kosong') }}
                            @endif
                        </td>
                        <td class="text-malap">{{ $gerak->labelSebab() ?? __('wky.umum.kosong') }}</td>
                        <td class="text-right font-medium">
                            {{ $gerak->kuantiti }}
                            @if ($gerak->batch)
                                <span class="mt-0.5 block text-xs font-normal text-malap">{{ $gerak->batch->no_batch }}</span>
                            @endif
                        </td>
                        {{--
                            Kos yang tidak direkod dipaparkan sebagai teks dan
                            bukan RM 0.00. Pergerakan lama berlaku sebelum kos
                            mula disimpan, dan sifar akan mendakwa barang itu
                            memang percuma.
                        --}}
                        <td class="text-right whitespace-nowrap">
                            @if ($gerak->kos_seunit === null)
                                <span class="text-malap">{{ __('wky.stok.kos_tidak_direkod') }}</span>
                            @else
                                {{ number_format((float) $gerak->kos_seunit, 2) }}
                                <span class="mt-0.5 block text-xs font-normal text-malap">
                                    {{ number_format($gerak->nilaiKos(), 2) }}
                                </span>
                            @endif
                        </td>
                        <td class="text-right text-malap">{{ $gerak->stok_sebelum }}</td>
                        <td class="text-right font-medium">{{ $gerak->stok_selepas }}</td>
                        <td>
                            {{ $gerak->rujukan ?? __('wky.umum.kosong') }}
                            @if ($gerak->no_do)
                                <span class="mt-0.5 block text-xs text-malap">{{ $gerak->no_do }}</span>
                            @endif
                        </td>
                        <td class="text-malap">{{ $gerak->user?->name ?? __('wky.umum.kosong') }}</td>
                        <td>
                            <div class="flex justify-end">
                                @if ($gerak->adaDeliveryOrder())
                                    <a href="{{ route('stock.do', $gerak) }}" class="btn-garis btn-ikon" title="{{ __('wky.do.tajuk') }}">
                                        <x-ikon nama="pencetak" kelas="size-4" />
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="py-10 text-center text-malap">{{ __('wky.stok.tiada_rekod') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($movements->hasPages())
            <div class="kad-kaki penomboran block">{{ $movements->links() }}</div>
        @endif
    </div>
@endsection
