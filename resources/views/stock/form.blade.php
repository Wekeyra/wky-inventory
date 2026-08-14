@extends('layouts.app')
@section('tajuk', __('wky.stok.rekod_tajuk'))

@section('kandungan')
    <form method="POST" action="{{ route('stock.store') }}" class="kad max-w-2xl" id="borangStok">
        @csrf

        <div class="kad-badan space-y-4">
            <div>
                <label for="imbasProduk" class="mb-1 block font-medium">{{ __('wky.barcode.cari_produk') }}</label>
                <div class="flex gap-2">
                    <input id="imbasProduk" autocomplete="off" placeholder="{{ __('wky.barcode.cari_placeholder') }}">
                    <x-imbas-barcode sasaran="imbasProduk" />
                </div>
                <p class="mt-1 text-xs text-malap" id="imbasStatus">{{ __('wky.barcode.nota_cari') }}</p>
            </div>

            <div>
                <label for="product_id" class="mb-1 block font-medium">{{ __('wky.medan.produk') }} <span class="text-bahaya">*</span></label>

                {{-- Butang yang sama seperti pada modal stok pantas: produk yang
                     hilang disedari tepat semasa cuba merekod stoknya, bukan
                     semasa melawat halaman Produk. --}}
                <div class="flex gap-2">
                <select id="product_id" name="product_id" class="min-w-0" required @error('product_id') class="medan-ralat" @enderror>
                    <option value="">{{ __('wky.umum.pilih_produk') }}</option>
                    @foreach ($products as $produk)
                        {{--
                            Barcode dan SKU dibawa sebagai data-* supaya imbasan
                            dipadankan dalam pelayar. Senarai produk sudah pun
                            ada di halaman ini; satu lagi pusingan ke pelayan
                            hanya menambah kelewatan pada langkah yang sepatutnya
                            terasa serta-merta.
                        --}}
                        <option value="{{ $produk->id }}" @selected(old('product_id', $terpilih) == $produk->id)
                                data-barcode="{{ $produk->barcode }}" data-sku="{{ $produk->sku }}"
                                data-jejak="{{ $produk->jejak_batch ? '1' : '' }}"
                                data-unit="{{ $produk->unit }}"
                                data-kos="{{ $produk->harga_kos }}"
                                data-baki="{{ $produk->balances->pluck('kuantiti', 'location_id')->toJson() }}">
                            {{ $produk->nama }} ({{ $produk->sku }}) — {{ __('wky.stok.baki') }} {{ $produk->stok }} {{ $produk->unit }}
                        </option>
                    @endforeach
                </select>

                    <a href="{{ route('products.create', ['kembali' => 'stok']) }}"
                       class="btn-garis btn-ikon shrink-0" title="{{ __('wky.produk.tambah') }}"
                       aria-label="{{ __('wky.produk.tambah') }}">
                        <x-ikon nama="tambah" />
                    </a>
                </div>
                @error('product_id') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="location_id" class="mb-1 block font-medium">{{ __('wky.medan.lokasi') }} <span class="text-bahaya">*</span></label>
                <select id="location_id" name="location_id" required @error('location_id') class="medan-ralat" @enderror>
                    @foreach ($locations as $lokasi)
                        <option value="{{ $lokasi->id }}" @selected(old('location_id', $lokasiTerpilih) == $lokasi->id)>{{ $lokasi->nama }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-malap" id="bakiLokasi"></p>
                @error('location_id') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="jenis" class="mb-1 block font-medium">{{ __('wky.medan.jenis') }} <span class="text-bahaya">*</span></label>
                    <select id="jenis" name="jenis" required @error('jenis') class="medan-ralat" @enderror>
                        <option value="masuk" @selected(old('jenis', $jenisAwal) === 'masuk')>{{ __('wky.stok.masuk_tambah') }}</option>
                        <option value="keluar" @selected(old('jenis', $jenisAwal) === 'keluar')>{{ __('wky.stok.keluar_tolak') }}</option>
                        <option value="pelarasan" @selected(old('jenis', $jenisAwal) === 'pelarasan')>{{ __('wky.stok.pelarasan_set') }}</option>
                    </select>
                    @error('jenis') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="kuantiti" class="mb-1 block font-medium">{{ __('wky.medan.kuantiti') }} <span class="text-bahaya">*</span></label>
                    <input type="number" min="1" id="kuantiti" name="kuantiti" value="{{ old('kuantiti') }}" required @error('kuantiti') class="medan-ralat" @enderror>
                    @error('kuantiti') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="sebab" class="mb-1 block font-medium">{{ __('wky.medan.sebab') }} <span class="text-bahaya">*</span></label>
                <select id="sebab" name="sebab" data-sebab required @error('sebab') class="medan-ralat" @enderror>
                    @foreach ($sebabPilihan as $jenisSebab => $senarai)
                        @foreach ($senarai as $nilai => $label)
                            <option value="{{ $nilai }}" data-jenis="{{ $jenisSebab }}" @selected(old('sebab') === $nilai && old('jenis', $jenisAwal) === $jenisSebab)>{{ $label }}</option>
                        @endforeach
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-malap">{{ __('wky.stok.nota_sebab') }}</p>
                @error('sebab') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            {{--
                Kos hanya diminta pada stok masuk. Stok keluar tidak memilih
                kosnya sendiri: kos barang yang keluar ialah kos barang itu
                semasa ia masuk, dan pengawal mengambilnya daripada lot atau
                daripada harga kos produk.
            --}}
            <div class="hidden" data-masuk-sahaja>
                <label for="kos_seunit" class="mb-1 block font-medium">{{ __('wky.medan.kos_seunit') }}</label>
                <input type="number" step="0.01" min="0" id="kos_seunit" name="kos_seunit"
                       value="{{ old('kos_seunit') }}" @error('kos_seunit') class="medan-ralat" @enderror>
                <p class="mt-1 text-xs text-malap">{{ __('wky.stok.nota_kos') }}</p>
                @error('kos_seunit') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            {{--
                Medan batch hanya muncul untuk produk yang dijejak batchnya.
                Kelas 'hidden' diletakkan pada pembungkus dan bukan pada grid itu
                sendiri, kerana kedua-duanya menetapkan 'display' dan yang terakhir
                dijana dalam CSS akan menang — grid yang sepatutnya tersembunyi
                boleh kekal kelihatan.
            --}}
            <div class="hidden" data-batch-masuk>
              <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="no_batch" class="mb-1 block font-medium">{{ __('wky.batch.no_batch') }} <span class="text-bahaya">*</span></label>
                    <input id="no_batch" name="no_batch" value="{{ old('no_batch') }}" @error('no_batch') class="medan-ralat" @enderror>
                    @error('no_batch') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tarikh_luput" class="mb-1 block font-medium">{{ __('wky.batch.tarikh_luput') }}</label>
                    <input type="date" id="tarikh_luput" name="tarikh_luput" value="{{ old('tarikh_luput') }}">
                </div>

                <div>
                    <label for="no_siri" class="mb-1 block font-medium">{{ __('wky.batch.no_siri') }}</label>
                    <input id="no_siri" name="no_siri" value="{{ old('no_siri') }}">
                </div>
              </div>
            </div>

            <div class="hidden" data-batch-keluar>
                <label for="product_batch_id" class="mb-1 block font-medium">{{ __('wky.batch.pilih') }} <span class="text-bahaya">*</span></label>
                <select id="product_batch_id" name="product_batch_id" @error('product_batch_id') class="medan-ralat" @enderror>
                    <option value="">{{ __('wky.batch.pilih_kosong') }}</option>
                    @foreach ($products as $produk)
                        @foreach ($produk->batches as $batch)
                            {{-- Lot disusun mengikut tarikh luput terawal, jadi yang paling hampir tamat keluar dahulu. --}}
                            <option value="{{ $batch->id }}" data-produk="{{ $produk->id }}" class="hidden"
                                    @selected(old('product_batch_id') == $batch->id)>
                                {{ $batch->no_batch }} — {{ $batch->kuantiti }} {{ $produk->unit }}
                                @if ($batch->tarikh_luput) ({{ $batch->tarikh_luput->format('d/m/Y') }}) @endif
                            </option>
                        @endforeach
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-malap">{{ __('wky.batch.nota_pilih') }}</p>
                @error('product_batch_id') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="rujukan" class="mb-1 block font-medium">{{ __('wky.medan.rujukan') }}</label>
                    <input id="rujukan" name="rujukan" value="{{ old('rujukan') }}" placeholder="{{ __('wky.stok.rujukan_placeholder') }}">
                </div>

                <div class="hidden" data-keluar-sahaja>
                    <label for="penerima" class="mb-1 block font-medium">{{ __('wky.medan.penerima') }}</label>
                    <input id="penerima" name="penerima" value="{{ old('penerima') }}" placeholder="{{ __('wky.stok.penerima_placeholder') }}">
                </div>
            </div>

            <div>
                <label for="catatan" class="mb-1 block font-medium">{{ __('wky.medan.catatan') }}</label>
                <textarea id="catatan" name="catatan" rows="2">{{ old('catatan') }}</textarea>
            </div>

            <div class="amaran-info">
                <span>{!! __('wky.stok.nota_pelarasan', ['pelarasan' => '<strong>' . e(__('wky.stok.pelarasan')) . '</strong>']) !!}</span>
            </div>

            <div class="amaran-info hidden" data-keluar-sahaja>
                <span>{{ __('wky.stok.nota_do') }}</span>
            </div>
        </div>

        <div class="kad-kaki">
            <button type="submit" class="btn-utama">{{ __('wky.aksi.rekod') }}</button>
            <a href="{{ route('stock.index') }}" class="btn-garis">{{ __('wky.aksi.batal') }}</a>
        </div>
    </form>
@endsection

@push('skrip')
    <script>
        (function () {
            const borang = document.getElementById('borangStok');
            const produk = document.getElementById('product_id');
            const jenis = document.getElementById('jenis');
            const imbas = document.getElementById('imbasProduk');
            const status = document.getElementById('imbasStatus');
            const pilihBatch = document.getElementById('product_batch_id');
            const kotakMasuk = borang.querySelector('[data-batch-masuk]');
            const kotakKeluar = borang.querySelector('[data-batch-keluar]');
            const medanBatch = kotakMasuk.querySelector('#no_batch');
            const medanKos = document.getElementById('kos_seunit');

            const lokasi = document.getElementById('location_id');
            const paparBaki = document.getElementById('bakiLokasi');

            {{--
                Array dibina dalam @php dahulu: @json dengan array berbilang
                baris terus di dalamnya menghasilkan PHP yang tidak sah —
                kurungan penutupnya tercicir semasa Blade menghurai argumen
                arahan itu.
            --}}
            @php($teksJs = [
                'tiada' => __('wky.barcode.tiada_padanan'),
                'nota' => __('wky.barcode.nota_cari'),
                'baki' => __('wky.stok.baki_lokasi'),
            ])

            const teks = @json($teksJs);

            // Kod yang diimbas atau ditaip dipadankan dengan barcode dahulu,
            // kemudian SKU. Pengimbas USB menaip kod diikuti Enter, jadi
            // peristiwa input di sini menangkap kedua-dua cara.
            imbas.addEventListener('input', function () {
                const kod = imbas.value.trim().toLowerCase();

                if (kod === '') {
                    status.textContent = teks.nota;

                    return;
                }

                const padan = [...produk.options].find((pilihan) =>
                    (pilihan.dataset.barcode || '').toLowerCase() === kod
                    || (pilihan.dataset.sku || '').toLowerCase() === kod);

                status.textContent = padan ? padan.textContent.trim() : teks.tiada;

                if (padan) {
                    produk.value = padan.value;
                    produk.dispatchEvent(new Event('change'));
                }
            });

            // Enter pada medan imbasan tidak boleh menghantar borang: pengimbas
            // USB menghantar Enter selepas setiap kod, dan borang yang terhantar
            // pada kod pertama tidak akan sempat menerima kuantiti.
            imbas.addEventListener('keydown', function (peristiwa) {
                if (peristiwa.key === 'Enter') {
                    peristiwa.preventDefault();
                }
            });

            function segarkan() {
                const pilihan = produk.selectedOptions[0];
                const jejak = Boolean(pilihan?.dataset.jejak);
                const arah = jenis.value;

                // Baki gudang yang dipilih ditunjukkan sebelum borang dihantar,
                // supaya penolakan yang melebihi baki gudang itu dapat dilihat
                // di sini dan bukan hanya sebagai ralat selepas menekan Rekod.
                if (pilihan?.value) {
                    const baki = JSON.parse(pilihan.dataset.baki || '{}')[lokasi.value] ?? 0;

                    paparBaki.textContent = teks.baki
                        .replace(':baki', baki)
                        .replace(':unit', pilihan.dataset.unit || '');
                } else {
                    paparBaki.textContent = '';
                }

                kotakMasuk.classList.toggle('hidden', ! (jejak && arah === 'masuk'));
                kotakKeluar.classList.toggle('hidden', ! (jejak && arah === 'keluar'));

                borang.querySelectorAll('[data-keluar-sahaja]')
                    .forEach((el) => el.classList.toggle('hidden', arah !== 'keluar'));

                borang.querySelectorAll('[data-masuk-sahaja]')
                    .forEach((el) => el.classList.toggle('hidden', arah !== 'masuk'));

                // Medan kos dilumpuhkan apabila tersembunyi, bukan sekadar
                // disembunyikan. Peraturan pengesahan menolak kos pada stok
                // keluar, jadi nilai yang tertinggal daripada saat pengguna
                // memilih "masuk" akan menyebabkan borang ditolak tanpa
                // sebarang medan yang kelihatan untuk dibetulkan.
                medanKos.disabled = arah !== 'masuk';

                // Harga kos produk ditunjukkan sebagai placeholder dan bukan
                // ditulis sebagai nilai: pengawal sudah jatuh kepada harga itu
                // apabila medan dibiarkan kosong, dan nilai yang ditulis sendiri
                // akan menjadi angka yang pengguna sangka dia sahkan.
                medanKos.placeholder = pilihan?.dataset.kos ?? '';

                // required diselaraskan dengan apa yang kelihatan, kerana medan
                // wajib yang tersembunyi menghalang penghantaran tanpa
                // menunjukkan kepada pengguna medan mana yang menahannya.
                medanBatch.required = jejak && arah === 'masuk';
                pilihBatch.required = jejak && arah === 'keluar';

                // Hanya lot milik produk terpilih yang boleh dipilih.
                [...pilihBatch.options].forEach((pilihan) => {
                    const padan = ! pilihan.value || pilihan.dataset.produk === produk.value;

                    pilihan.hidden = ! padan;
                    pilihan.disabled = ! padan;
                });

                if (pilihBatch.selectedOptions[0]?.disabled) {
                    pilihBatch.value = '';
                }
            }

            produk.addEventListener('change', segarkan);
            jenis.addEventListener('change', segarkan);
            lokasi.addEventListener('change', segarkan);
            segarkan();
        })();
    </script>
@endpush
