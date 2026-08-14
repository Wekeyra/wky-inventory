@extends('layouts.app')
@section('tajuk', __('wky.po.tajuk_papar', ['kod' => $pesanan->kod]))

@section('kandungan')
    <div class="space-y-6">
        <div class="kad">
            <div class="kad-kepala">
                <span class="flex items-center gap-2 font-semibold">
                    <code>{{ $pesanan->kod }}</code>
                    <span class="{{ $pesanan->kelasStatus() }}">{{ $pesanan->labelStatus() }}</span>
                </span>

                <div class="flex flex-wrap gap-2">
                    @if ($pesanan->bolehDisunting())
                        <a href="{{ route('purchase-orders.edit', $pesanan) }}" class="btn-garis btn-kecil">
                            <x-ikon nama="pensel" kelas="size-4" /> {{ __('wky.aksi.kemas_kini') }}
                        </a>

                        <form method="POST" action="{{ route('purchase-orders.submit', $pesanan) }}">
                            @csrf
                            <button type="submit" class="btn-utama btn-kecil">{{ __('wky.po.hantar') }}</button>
                        </form>

                        <form method="POST" action="{{ route('purchase-orders.destroy', $pesanan) }}"
                              onsubmit="return confirm(@json(__('wky.po.sahkan_padam', ['kod' => $pesanan->kod])))">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-bahaya btn-kecil">
                                <x-ikon nama="tong-sampah" kelas="size-4" /> {{ __('wky.aksi.padam') }}
                            </button>
                        </form>
                    @endif

                    @if ($pesanan->bolehKe('dibatalkan') && ! $pesanan->bolehDisunting())
                        <form method="POST" action="{{ route('purchase-orders.cancel', $pesanan) }}"
                              onsubmit="return confirm(@json(__('wky.po.sahkan_batal', ['kod' => $pesanan->kod])))">
                            @csrf
                            <button type="submit" class="btn-bahaya btn-kecil">{{ __('wky.po.batalkan') }}</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="kad-badan grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="label-stat">{{ __('wky.po.pembekal') }}</p>
                    <p class="mt-1">{{ $pesanan->supplier?->nama ?? __('wky.umum.kosong') }}</p>
                </div>
                <div>
                    <p class="label-stat">{{ __('wky.po.dipohon_oleh') }}</p>
                    <p class="mt-1">{{ $pesanan->pemohon?->name ?? __('wky.umum.kosong') }}</p>
                </div>
                <div>
                    <p class="label-stat">{{ __('wky.po.tarikh_diperlukan') }}</p>
                    <p class="mt-1">{{ $pesanan->tarikh_diperlukan?->format('d/m/Y') ?? __('wky.umum.kosong') }}</p>
                </div>
                <div>
                    <p class="label-stat">{{ __('wky.po.jumlah_nilai') }}</p>
                    <p class="nilai-stat mt-1">{{ number_format($pesanan->jumlahNilai(), 2) }}</p>
                </div>

                @if ($pesanan->diputuskan_pada)
                    <div class="sm:col-span-2 lg:col-span-4">
                        <p class="label-stat">{{ __('wky.po.diputuskan_oleh') }}</p>
                        <p class="mt-1">
                            {{ $pesanan->pemutus?->name ?? __('wky.umum.kosong') }}
                            <span class="text-malap">— {{ $pesanan->diputuskan_pada->format('d/m/Y H:i') }}</span>
                        </p>
                    </div>
                @endif

                @if ($pesanan->sebab_tolak)
                    <div class="sm:col-span-2 lg:col-span-4">
                        <p class="label-stat">{{ __('wky.po.sebab_tolak') }}</p>
                        <p class="mt-1">{{ $pesanan->sebab_tolak }}</p>
                    </div>
                @endif

                @if ($pesanan->catatan)
                    <div class="sm:col-span-2 lg:col-span-4">
                        <p class="label-stat">{{ __('wky.medan.catatan') }}</p>
                        <p class="mt-1">{{ $pesanan->catatan }}</p>
                    </div>
                @endif
            </div>

            @if ($pesanan->status === 'draf')
                <div class="kad-kaki block text-xs text-malap">{{ __('wky.po.nota_draf') }}</div>
            @elseif ($pesanan->status === 'menunggu')
                <div class="kad-kaki block text-xs text-malap">{{ __('wky.po.nota_menunggu') }}</div>
            @endif
        </div>

        {{--
            Keputusan hanya untuk admin. Laluannya sendiri dijaga middleware
            admin; borang ini disembunyikan supaya staf tidak ditawarkan butang
            yang akan ditolak apabila ditekan.
        --}}
        @if ($pesanan->status === 'menunggu' && auth()->user()->isAdmin())
            <form method="POST" action="{{ route('purchase-orders.decide', $pesanan) }}" class="kad">
                @csrf

                <div class="kad-badan space-y-3">
                    <div>
                        <label for="sebab_tolak" class="mb-1 block font-medium">{{ __('wky.po.sebab_tolak') }}</label>
                        <textarea id="sebab_tolak" name="sebab_tolak" rows="2"
                                  placeholder="{{ __('wky.umum.kosong') }}">{{ old('sebab_tolak') }}</textarea>
                        @error('sebab_tolak') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="kad-kaki">
                    <button type="submit" name="keputusan" value="diluluskan" class="btn-utama">
                        <x-ikon nama="tanda-semak" kelas="size-4" /> {{ __('wky.po.lulus') }}
                    </button>
                    <button type="submit" name="keputusan" value="ditolak" class="btn-bahaya">
                        <x-ikon nama="silang-bulat" kelas="size-4" /> {{ __('wky.po.tolak') }}
                    </button>
                </div>
            </form>
        @endif

        <form method="POST" action="{{ route('purchase-orders.receive', $pesanan) }}" class="kad">
            @csrf

            <div class="kad-kepala">
                <span class="font-semibold">{{ __('wky.po.barang') }}</span>

                @if ($pesanan->bolehTerima())
                    <div class="flex items-center gap-2">
                        <label for="location_id" class="text-sm text-malap">{{ __('wky.medan.lokasi') }}</label>
                        <select id="location_id" name="location_id" class="!w-auto min-w-44">
                            @foreach ($locations as $lokasi)
                                <option value="{{ $lokasi->id }}" @selected($lokasiLalai == $lokasi->id)>{{ $lokasi->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="jadual">
                    <thead>
                        <tr>
                            <th>{{ __('wky.medan.produk') }}</th>
                            <th class="text-right">{{ __('wky.po.dipesan') }}</th>
                            <th class="text-right">{{ __('wky.po.diterima') }}</th>
                            <th class="text-right">{{ __('wky.po.baki_terima') }}</th>
                            <th class="text-right">{{ __('wky.medan.kos_seunit') }}</th>
                            <th class="text-right">{{ __('wky.po.jumlah_nilai') }}</th>
                            @if ($pesanan->bolehTerima())
                                <th class="text-right">{{ __('wky.po.terima_kuantiti') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($pesanan->items as $item)
                        <tr>
                            <td>
                                <a href="{{ route('products.show', $item->product_id) }}" class="pautan-jadual">
                                    {{ $item->product?->nama ?? __('wky.umum.kosong') }}
                                </a>
                                <p class="text-xs text-malap">{{ $item->product?->sku }}</p>
                            </td>
                            <td class="text-right">{{ $item->kuantiti }}</td>
                            <td class="text-right">{{ $item->kuantiti_diterima }}</td>
                            <td class="text-right font-medium">{{ $item->bakiTerima() }}</td>
                            <td class="text-right whitespace-nowrap">
                                @if ($item->kos_seunit === null)
                                    <span class="text-malap">{{ __('wky.stok.kos_tidak_direkod') }}</span>
                                @else
                                    {{ number_format((float) $item->kos_seunit, 2) }}
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                {{ $item->nilai() === null ? __('wky.umum.kosong') : number_format($item->nilai(), 2) }}
                            </td>
                            @if ($pesanan->bolehTerima())
                                <td class="text-right">
                                    {{-- max ditetapkan kepada baki supaya pelayar menahan
                                         lebihan sebelum ia sampai ke pelayan; pengawal tetap
                                         menyemaknya semula, kerana atribut HTML boleh dibuang. --}}
                                    <input type="number" min="0" max="{{ $item->bakiTerima() }}"
                                           name="terima[{{ $item->id }}]" value=""
                                           class="!w-24" @disabled($item->bakiTerima() === 0)>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if ($pesanan->bolehTerima())
                <div class="kad-kaki flex-col items-start gap-2 sm:flex-row sm:items-center">
                    <button type="submit" class="btn-utama">
                        <x-ikon nama="masuk" kelas="size-4" /> {{ __('wky.po.terima') }}
                    </button>
                    <span class="text-xs text-malap">{{ __('wky.po.nota_separa') }}</span>
                </div>
            @endif
        </form>

        @if ($pesanan->bolehTerima())
            <div class="amaran-info">
                <x-ikon nama="amaran" kelas="size-5 shrink-0" />
                <span>{{ __('wky.po.nota_terima') }}</span>
            </div>
        @endif

        <a href="{{ route('purchase-orders.index') }}" class="btn-garis">{{ __('wky.aksi.kembali') }}</a>
    </div>
@endsection
