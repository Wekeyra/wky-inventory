@extends('layouts.app')
@section('tajuk', $product->nama)

@section('kandungan')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="kad flex flex-col">
            @if ($product->laluan_gambar)
                <img src="{{ route('products.gambar', $product) }}" alt="{{ $product->nama }}"
                     class="max-h-56 w-full rounded-t-lg border-b border-bingkai object-cover">
            @endif

            <div class="kad-badan flex-1">
                <dl class="grid grid-cols-2 gap-y-3 text-sm">
                    <dt class="text-malap">{{ __('wky.medan.sku') }}</dt>
                    <dd><code>{{ $product->sku }}</code></dd>

                    <dt class="text-malap">{{ __('wky.medan.barcode') }}</dt>
                    <dd>
                        @if ($product->barcode)
                            <code>{{ $product->barcode }}</code>
                        @else
                            {{ __('wky.umum.kosong') }}
                        @endif
                    </dd>

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

        <div class="lg:col-span-2 lg:space-y-4">
        <div class="kad mb-4 lg:mb-0">
            <div class="kad-kepala">
                <span class="flex items-center gap-2 font-semibold">
                    <x-ikon nama="gudang" kelas="size-5 text-merah" />
                    {{ __('wky.lokasi.baki_tajuk') }}
                </span>
                @if ($product->bezaLokasi() !== 0)
                    {{--
                        Jumlah stok sepatutnya sama dengan hasil tambah baki
                        gudang dan stok dalam perjalanan. Perbezaan bermakna ada
                        aliran yang menyentuh jumlah tanpa menyentuh gudang, dan
                        itu perlu dilihat sebelum ia menjadi kebiasaan.
                    --}}
                    <span class="lencana-kuning">{{ __('wky.lokasi.beza', ['beza' => $product->bezaLokasi()]) }}</span>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="jadual">
                    <thead>
                        <tr>
                            <th>{{ __('wky.medan.lokasi') }}</th>
                            <th>{{ __('wky.lokasi.rak') }}</th>
                            <th class="text-right">{{ __('wky.medan.kuantiti') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($balances as $baris)
                        <tr>
                            <td>
                                <a href="{{ route('locations.show', $baris['lokasi']) }}" class="pautan-jadual">{{ $baris['lokasi']->nama }}</a>
                                @if ($baris['lokasi']->lalai)
                                    <span class="lencana-biru ml-1">{{ __('wky.lokasi.lalai') }}</span>
                                @endif
                            </td>
                            <td class="text-malap">{{ $baris['baki']?->rak ?: __('wky.umum.kosong') }}</td>
                            <td class="text-right font-medium">{{ (int) $baris['baki']?->kuantiti }} {{ $product->unit }}</td>
                        </tr>
                    @endforeach

                    @if ($product->dalamPerjalanan() > 0)
                        <tr>
                            <td class="text-malap">{{ __('wky.pindah.dalam_perjalanan') }}</td>
                            <td class="text-malap">{{ __('wky.umum.kosong') }}</td>
                            <td class="text-right font-medium">{{ $product->dalamPerjalanan() }} {{ $product->unit }}</td>
                        </tr>
                    @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">{{ __('wky.produk.stok_semasa') }}</td>
                            <td class="text-right">{{ $product->stok }} {{ $product->unit }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if ($product->jejak_batch)
            <div class="kad mb-4 lg:mb-0">
                <div class="kad-kepala">
                    <span class="flex items-center gap-2 font-semibold">
                        <x-ikon nama="lapisan" kelas="size-5 text-merah" />
                        {{ __('wky.batch.tajuk') }}
                    </span>
                    @if ($product->bezaBatch() !== 0)
                        {{--
                            Pelarasan menyeluruh dan pengesahan kiraan stok menetapkan
                            baki produk tanpa menyebut lot, jadi kedua-dua nombor boleh
                            terpesong. Ia dipaparkan dan bukan disembunyikan, kerana
                            angka batch yang senyap-senyap salah lebih memudaratkan
                            daripada angka yang jelas tidak sepadan.
                        --}}
                        <span class="lencana-kuning">{{ __('wky.batch.beza', ['beza' => $product->bezaBatch()]) }}</span>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="jadual">
                        <thead>
                            <tr>
                                <th>{{ __('wky.batch.no_batch') }}</th>
                                <th>{{ __('wky.batch.no_siri') }}</th>
                                <th>{{ __('wky.batch.tarikh_luput') }}</th>
                                <th class="text-right">{{ __('wky.medan.kuantiti') }}</th>
                                <th class="text-right">{{ __('wky.medan.tindakan') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($batches as $batch)
                            <tr>
                                <td><code>{{ $batch->no_batch }}</code></td>
                                <td>
                                    <form method="POST" action="{{ route('products.batch.update', [$product, $batch]) }}"
                                          class="flex flex-wrap items-center gap-2" id="batch-{{ $batch->id }}">
                                        @csrf @method('PUT')
                                        <input name="no_siri" value="{{ $batch->no_siri }}" class="!w-32"
                                               placeholder="{{ __('wky.umum.kosong') }}">
                                    </form>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <input type="date" name="tarikh_luput" form="batch-{{ $batch->id }}"
                                               value="{{ $batch->tarikh_luput?->format('Y-m-d') }}" class="!w-40">
                                        <span class="{{ $batch->kelasLuput() }} whitespace-nowrap">{{ $batch->labelLuput() }}</span>
                                    </div>
                                </td>
                                <td class="text-right font-medium">{{ $batch->kuantiti }} {{ $product->unit }}</td>
                                <td class="text-right">
                                    <button type="submit" form="batch-{{ $batch->id }}" class="btn-garis btn-ikon"
                                            title="{{ __('wky.aksi.simpan') }}">
                                        <x-ikon nama="simpan" kelas="size-4" />
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-malap">{{ __('wky.batch.tiada') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="kad-kaki block text-xs text-malap">{{ __('wky.batch.nota_kuantiti') }}</div>
            </div>
        @endif

        <div class="kad">
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
                            <th>{{ __('wky.medan.sebab') }}</th>
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
                            <td class="text-malap">{{ $gerak->labelSebab() ?? __('wky.umum.kosong') }}</td>
                            <td class="text-right">
                                {{ $gerak->kuantiti }}
                                @if ($gerak->batch)
                                    <span class="mt-0.5 block text-xs text-malap">{{ $gerak->batch->no_batch }}</span>
                                @endif
                            </td>
                            <td class="text-right text-malap">{{ $gerak->stok_sebelum }}</td>
                            <td class="text-right font-medium">
                                {{ $gerak->stok_selepas }}
                                <span class="mt-0.5 block text-xs font-normal text-malap">
                                    @if ($gerak->isPindah())
                                        {{ $gerak->location?->nama }} → {{ $gerak->tujuan?->nama }}
                                    @else
                                        {{ $gerak->location?->nama }}
                                    @endif
                                </span>
                            </td>
                            <td>{{ $gerak->no_do ?? $gerak->rujukan ?? __('wky.umum.kosong') }}</td>
                            <td class="text-malap">{{ $gerak->user?->name ?? __('wky.umum.kosong') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-10 text-center text-malap">{{ __('wky.dashboard.tiada_pergerakan') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if ($movements->hasPages())
                <div class="kad-kaki penomboran block">{{ $movements->links() }}</div>
            @endif
        </div>
        </div>
    </div>
@endsection
