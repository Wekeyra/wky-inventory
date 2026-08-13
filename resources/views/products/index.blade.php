@extends('layouts.app')
@section('tajuk', __('wky.produk.tajuk'))

@section('kandungan')
    <div class="kad">
        <div class="kad-kepala">
            <form method="GET" class="grid w-full gap-2 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto_auto]">
                <div class="flex gap-2">
                    <input type="search" id="cari" name="cari" value="{{ $cari }}" placeholder="{{ __('wky.produk.cari_placeholder') }}">
                    {{-- Mengimbas terus menghantar borang: mencari kod yang baru diimbas ialah satu niat, bukan dua. --}}
                    <x-imbas-barcode sasaran="cari" hantar />
                </div>

                <select name="category_id">
                    <option value="">{{ __('wky.umum.semua_kategori') }}</option>
                    @foreach ($categories as $kategori)
                        <option value="{{ $kategori->id }}" @selected(request('category_id') == $kategori->id)>{{ $kategori->nama }}</option>
                    @endforeach
                </select>

                <label for="stok_rendah" class="flex cursor-pointer items-center gap-2 whitespace-nowrap">
                    <input type="checkbox" id="stok_rendah" name="stok_rendah" value="1" class="!w-auto" @checked(request()->boolean('stok_rendah'))>
                    {{ __('wky.produk.stok_rendah_sahaja') }}
                </label>

                <div class="flex gap-2">
                    <button type="submit" class="btn-garis btn-ikon" title="{{ __('wky.aksi.cari') }}"><x-ikon nama="cari" /></button>
                    <a href="{{ route('products.create') }}" class="btn-utama"><x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.aksi.tambah') }}</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.sku') }}</th>
                        <th>{{ __('wky.medan.nama') }}</th>
                        <th>{{ __('wky.medan.kategori') }}</th>
                        <th>{{ __('wky.medan.pembekal') }}</th>
                        <th class="text-right">{{ __('wky.medan.harga_jual') }}</th>
                        <th class="text-right">{{ __('wky.medan.stok') }}</th>
                        <th class="text-right">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($products as $produk)
                    <tr>
                        <td>
                            <code>{{ $produk->sku }}</code>
                            @if ($produk->barcode)
                                <span class="mt-0.5 block text-xs text-malap">{{ $produk->barcode }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                @if ($produk->laluan_gambar)
                                    <img src="{{ route('products.gambar', $produk) }}" alt="" loading="lazy"
                                         class="size-9 shrink-0 rounded border border-bingkai object-cover">
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('products.show', $produk) }}" class="pautan-jadual">{{ $produk->nama }}</a>
                                    @unless ($produk->aktif)
                                        <span class="lencana-kelabu ml-1">{{ __('wky.umum.tidak_aktif') }}</span>
                                    @endunless
                                    @if ($produk->jejak_batch)
                                        <span class="lencana-biru ml-1">{{ __('wky.produk.lencana_batch') }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $produk->category?->nama ?? __('wky.umum.kosong') }}</td>
                        <td>{{ $produk->supplier?->nama ?? __('wky.umum.kosong') }}</td>
                        <td class="text-right whitespace-nowrap">RM {{ number_format($produk->harga_jual, 2) }}</td>
                        <td class="text-right">
                            <span class="{{ $produk->stok <= $produk->stok_minimum ? 'lencana-bahaya' : 'lencana-hijau' }}">
                                {{ $produk->stok }} {{ $produk->unit }}
                            </span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('stock.create', ['product_id' => $produk->id]) }}" class="btn-garis btn-ikon" title="{{ __('wky.aksi.rekod_stok') }}">
                                    <x-ikon nama="anak-panah-dua-arah" kelas="size-4" />
                                </a>
                                <a href="{{ route('products.edit', $produk) }}" class="btn-garis btn-ikon" title="{{ __('wky.aksi.kemas_kini') }}">
                                    <x-ikon nama="pensel" kelas="size-4" />
                                </a>
                                <form method="POST" action="{{ route('products.destroy', $produk) }}"
                                      onsubmit="return confirm('{{ __('wky.produk.sahkan_padam', ['nama' => $produk->nama]) }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-bahaya btn-ikon" title="{{ __('wky.aksi.padam') }}">
                                        <x-ikon nama="tong-sampah" kelas="size-4" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-malap">{{ __('wky.produk.tiada_dijumpai') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="kad-kaki penomboran block">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
