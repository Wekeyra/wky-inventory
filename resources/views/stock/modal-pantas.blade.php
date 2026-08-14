{{--
    Borang ringkas untuk merekod stok terus dari dashboard.
    Ia menghantar ke laluan stock.store yang sama seperti borang penuh, jadi
    semua peraturan validasi dan kunci transaksi terpakai tanpa pertindihan logik.
--}}
<div id="modal-stok-pantas" data-modal @if (old('sumber') === 'pantas') data-modal-auto @endif
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4 backdrop-blur-sm [&:not(.hidden)]:flex"
     role="dialog" aria-modal="true" aria-labelledby="tajuk-modal-pantas">
    <div class="kad w-full max-w-lg shadow-2xl">
        <form method="POST" action="{{ route('stock.store') }}">
            @csrf
            <input type="hidden" name="sumber" value="pantas">

            <div class="kad-kepala">
                <h2 id="tajuk-modal-pantas" class="flex items-center gap-2 font-semibold">
                    <x-ikon nama="tambah-bulat" kelas="size-5 text-aksen" />
                    {{ __('wky.dashboard.tambah_stok_pantas') }}
                </h2>
                <button type="button" class="cursor-pointer text-malap hover:text-teks" data-modal-tutup aria-label="{{ __('wky.aksi.batal') }}">
                    <x-ikon nama="silang" />
                </button>
            </div>

            <div class="kad-badan space-y-4">
                <div>
                    <label for="pantas_product_id" class="mb-1 block font-medium">{{ __('wky.medan.produk') }} <span class="text-bahaya">*</span></label>

                    {{--
                        Produk yang hilang paling kerap disedari di sini — tepat
                        semasa cuba merekod stoknya. Pemasangan baharu pula
                        bermula tanpa satu produk pun, jadi tanpa butang ini
                        borang pertama yang dilihat pengguna ialah borang yang
                        tidak boleh dihantar.

                        min-w-0 pada select dan shrink-0 pada butang: nama produk
                        boleh panjang, dan tanpa kedua-duanya select enggan
                        mengecil lalu menolak butang keluar daripada modal.
                    --}}
                    <div class="flex gap-2">
                        <select id="pantas_product_id" name="product_id" class="min-w-0" required>
                            <option value="">{{ __('wky.umum.pilih_produk') }}</option>
                            @foreach ($products as $produk)
                                <option value="{{ $produk->id }}" @selected(old('product_id') == $produk->id)>
                                    {{ $produk->nama }} ({{ $produk->sku }}) — {{ __('wky.stok.baki') }} {{ $produk->stok }} {{ $produk->unit }}
                                </option>
                            @endforeach
                        </select>

                        <a href="{{ route('products.create', ['kembali' => 'dashboard']) }}"
                           class="btn-garis btn-ikon shrink-0" title="{{ __('wky.produk.tambah') }}"
                           aria-label="{{ __('wky.produk.tambah') }}">
                            <x-ikon nama="tambah" />
                        </a>
                    </div>
                </div>

                {{--
                    Medan lokasi hilang sepenuhnya apabila gudang berbilang
                    dimatikan — bukan sekadar disembunyikan. Medan `required`
                    yang tersembunyi menahan penghantaran borang tanpa
                    menunjukkan kepada pengguna apa yang menahannya, dan dengan
                    satu gudang sahaja pilihan itu memang tiada makna.

                    Pengawal jatuh kepada Location::lalai() apabila tiada
                    lokasi dihantar, jadi tiada apa yang berubah pada rekod.
                --}}
                @if (auth()->user()->workspace?->adaCiri('gudang'))
                    <div>
                        <label for="pantas_location_id" class="mb-1 block font-medium">{{ __('wky.medan.lokasi') }} <span class="text-bahaya">*</span></label>
                        <select id="pantas_location_id" name="location_id" required>
                            @foreach ($locations as $lokasi)
                                <option value="{{ $lokasi->id }}" @selected(old('location_id', $lokasiLalai) == $lokasi->id)>{{ $lokasi->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="pantas_jenis" class="mb-1 block font-medium">{{ __('wky.medan.jenis') }} <span class="text-bahaya">*</span></label>
                        <select id="pantas_jenis" name="jenis" required>
                            <option value="masuk" @selected(old('jenis', 'masuk') === 'masuk')>{{ __('wky.stok.masuk') }}</option>
                            <option value="keluar" @selected(old('jenis') === 'keluar')>{{ __('wky.stok.keluar') }}</option>
                            <option value="pelarasan" @selected(old('jenis') === 'pelarasan')>{{ __('wky.stok.pelarasan') }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="pantas_kuantiti" class="mb-1 block font-medium">{{ __('wky.medan.kuantiti') }} <span class="text-bahaya">*</span></label>
                        <input type="number" min="1" id="pantas_kuantiti" name="kuantiti" value="{{ old('kuantiti') }}" required>
                    </div>
                </div>

                <div>
                    <label for="pantas_sebab" class="mb-1 block font-medium">{{ __('wky.medan.sebab') }} <span class="text-bahaya">*</span></label>
                    <select id="pantas_sebab" name="sebab" data-sebab required>
                        @foreach ($sebabPilihan as $jenisSebab => $senarai)
                            @foreach ($senarai as $nilai => $label)
                                <option value="{{ $nilai }}" data-jenis="{{ $jenisSebab }}" @selected(old('sebab') === $nilai && old('jenis', 'masuk') === $jenisSebab)>{{ $label }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="pantas_rujukan" class="mb-1 block font-medium">{{ __('wky.medan.rujukan') }}</label>
                    <input id="pantas_rujukan" name="rujukan" value="{{ old('rujukan') }}" placeholder="{{ __('wky.stok.rujukan_placeholder') }}">
                </div>

                {{--
                    Produk berbatch tidak boleh direkod dari borang pantas: ia
                    memerlukan nombor lot, dan borang ini sengaja pendek. Nota
                    ini mengarahkan pengguna ke borang penuh sebelum dia
                    terperanjat dengan ralat pengesahan.
                --}}
                <p class="text-xs text-malap">
                    {!! __('wky.stok.nota_pantas_batch', [
                        'pautan' => '<a class="underline" href="' . route('stock.create') . '">' . e(__('wky.stok.rekod_tajuk')) . '</a>',
                    ]) !!}
                </p>
            </div>

            <div class="kad-kaki justify-end">
                <button type="button" class="btn-garis" data-modal-tutup>{{ __('wky.aksi.batal') }}</button>
                <button type="submit" class="btn-utama">{{ __('wky.aksi.rekod') }}</button>
            </div>
        </form>
    </div>
</div>
