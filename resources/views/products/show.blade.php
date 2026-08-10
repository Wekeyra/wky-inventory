@extends('layouts.app')
@section('tajuk', $product->nama)

@section('kandungan')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="kad flex flex-col">
            <div class="kad-badan flex-1">
                <dl class="grid grid-cols-2 gap-y-3 text-sm">
                    <dt class="text-malap">{{ __('wky.medan.sku') }}</dt>
                    <dd><code>{{ $product->sku }}</code></dd>

                    <dt class="text-malap">{{ __('wky.medan.kategori') }}</dt>
                    <dd>{{ $product->category?->nama ?? __('wky.umum.kosong') }}</dd>

                    <dt class="text-malap">{{ __('wky.medan.pembekal') }}</dt>
                    <dd>{{ $product->supplier?->nama ?? __('wky.umum.kosong') }}</dd>

                    <dt class="text-malap">{{ __('wky.medan.harga_kos') }}</dt>
                    <dd>RM {{ number_format($product->harga_kos, 2) }}</dd>

                    <dt class="text-malap">{{ __('wky.medan.harga_jual') }}</dt>
                    <dd>RM {{ number_format($product->harga_jual, 2) }}</dd>

                    <dt class="text-malap">{{ __('wky.produk.stok_semasa') }}</dt>
                    <dd>
                        <span class="{{ $product->stok <= $product->stok_minimum ? 'lencana-merah' : 'lencana-hijau' }}">
                            {{ $product->stok }} {{ $product->unit }}
                        </span>
                    </dd>

                    <dt class="text-malap">{{ __('wky.medan.stok_minimum') }}</dt>
                    <dd>{{ $product->stok_minimum }}</dd>

                    <dt class="text-malap">{{ __('wky.produk.nilai_stok') }}</dt>
                    <dd>RM {{ number_format($product->nilaiStok(), 2) }}</dd>

                    <dt class="text-malap">{{ __('wky.medan.status') }}</dt>
                    <dd>
                        <span class="{{ $product->aktif ? 'lencana-hijau' : 'lencana-kelabu' }}">
                            {{ $product->aktif ? __('wky.umum.aktif') : __('wky.umum.tidak_aktif') }}
                        </span>
                    </dd>
                </dl>

                @if ($product->keterangan)
                    <p class="mt-4 border-t border-bingkai pt-4 text-sm text-malap">{{ $product->keterangan }}</p>
                @endif
            </div>

            <div class="kad-kaki">
                <a href="{{ route('products.edit', $product) }}" class="btn-utama btn-kecil"><x-ikon nama="pensel" kelas="size-4" /> {{ __('wky.aksi.kemas_kini') }}</a>
                <a href="{{ route('stock.create', ['product_id' => $product->id]) }}" class="btn-wky btn-kecil"><x-ikon nama="anak-panah-dua-arah" kelas="size-4" /> {{ __('wky.aksi.rekod_stok') }}</a>
                <a href="{{ route('products.index') }}" class="btn-garis btn-kecil ml-auto">{{ __('wky.aksi.kembali') }}</a>
            </div>
        </div>

        <div class="kad lg:col-span-2">
            <div class="kad-kepala">
                <span class="flex items-center gap-2 font-semibold">
                    <x-ikon nama="jam" kelas="size-5 text-merah" />
                    {{ __('wky.produk.sejarah_pergerakan') }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="jadual">
                    <thead>
                        <tr>
                            <th>{{ __('wky.medan.tarikh') }}</th>
                            <th>{{ __('wky.medan.jenis') }}</th>
                            <th class="text-right">{{ __('wky.medan.kuantiti') }}</th>
                            <th class="text-right">{{ __('wky.stok.sebelum') }}</th>
                            <th class="text-right">{{ __('wky.stok.selepas') }}</th>
                            <th>{{ __('wky.medan.rujukan') }}</th>
                            <th>{{ __('wky.medan.oleh') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($movements as $gerak)
                        <tr>
                            <td class="whitespace-nowrap text-malap">{{ $gerak->created_at->format('d/m/Y H:i') }}</td>
                            <td><span class="{{ $gerak->kelasJenis() }}">{{ $gerak->labelJenis() }}</span></td>
                            <td class="text-right">{{ $gerak->kuantiti }}</td>
                            <td class="text-right text-malap">{{ $gerak->stok_sebelum }}</td>
                            <td class="text-right font-medium">{{ $gerak->stok_selepas }}</td>
                            <td>{{ $gerak->rujukan ?? __('wky.umum.kosong') }}</td>
                            <td class="text-malap">{{ $gerak->user?->name ?? __('wky.umum.kosong') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-malap">{{ __('wky.dashboard.tiada_pergerakan') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if ($movements->hasPages())
                <div class="kad-kaki penomboran block">{{ $movements->links() }}</div>
            @endif
        </div>
    </div>
@endsection
