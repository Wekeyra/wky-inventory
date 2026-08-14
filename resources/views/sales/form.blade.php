@extends('layouts.app')
@section('tajuk', __('wky.jual.tambah'))

@section('kandungan')
    <form method="POST" action="{{ route('sales.store') }}" class="kad max-w-4xl">
        @csrf

        <div class="kad-badan space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="pelanggan" class="mb-1 block font-medium">{{ __('wky.jual.pelanggan') }}</label>
                    <input id="pelanggan" name="pelanggan" value="{{ old('pelanggan') }}">
                    @error('pelanggan') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="location_id" class="mb-1 block font-medium">{{ __('wky.medan.lokasi') }}</label>
                    <select id="location_id" name="location_id">
                        @foreach ($locations as $lokasi)
                            <option value="{{ $lokasi->id }}" @selected(old('location_id', $lokasiTerpilih) == $lokasi->id)>{{ $lokasi->nama }}</option>
                        @endforeach
                    </select>
                    @error('location_id') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between">
                    <span class="font-medium">{{ __('wky.jual.barang') }} <span class="text-bahaya">*</span></span>
                    <button type="button" class="btn-garis btn-kecil" id="tambahBaris">
                        <x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.jual.tambah_baris') }}
                    </button>
                </div>

                <div class="space-y-2" id="senaraiBaris">
                    @php($lama = old('baris', [['product_id' => '', 'kuantiti' => '', 'harga_jual' => '', 'product_batch_id' => '']]))

                    @foreach ($lama as $i => $baris)
                        <div class="flex flex-wrap gap-2" data-baris>
                            <select name="baris[{{ $i }}][product_id]" data-produk class="min-w-0 flex-1">
                                <option value="">{{ __('wky.umum.pilih_produk') }}</option>
                                @foreach ($products as $produk)
                                    {{-- Harga jual dan penjejakan batch dibawa sebagai data-*
                                         supaya baris dapat menyesuaikan dirinya tanpa memuat
                                         semula halaman. --}}
                                    <option value="{{ $produk->id }}" @selected(($baris['product_id'] ?? '') == $produk->id)
                                            data-harga="{{ $produk->harga_jual }}"
                                            data-jejak="{{ $produk->jejak_batch ? '1' : '' }}">
                                        {{ $produk->nama }} ({{ $produk->sku }}) — {{ __('wky.stok.baki') }} {{ $produk->stok }} {{ $produk->unit }}
                                    </option>
                                @endforeach
                            </select>

                            <input type="number" min="1" name="baris[{{ $i }}][kuantiti]" value="{{ $baris['kuantiti'] ?? '' }}"
                                   placeholder="{{ __('wky.medan.kuantiti') }}" class="!w-24">

                            <input type="number" step="0.01" min="0" name="baris[{{ $i }}][harga_jual]"
                                   value="{{ $baris['harga_jual'] ?? '' }}" data-harga
                                   placeholder="{{ __('wky.jual.harga_jual') }}" class="!w-32">

                            {{-- Pemilih lot tersembunyi sehingga produk yang dijejak batchnya
                                 dipilih, dan dilumpuhkan ketika tersembunyi supaya lot yang
                                 tertinggal daripada pilihan sebelumnya tidak terhantar. --}}
                            <select name="baris[{{ $i }}][product_batch_id]" data-lot class="hidden !w-48" disabled>
                                <option value="">{{ __('wky.jual.pilih_lot') }}</option>
                                @foreach ($products as $produk)
                                    @foreach ($produk->batches as $lot)
                                        <option value="{{ $lot->id }}" data-produk="{{ $produk->id }}" class="hidden"
                                                @selected(($baris['product_batch_id'] ?? '') == $lot->id)>
                                            {{ $lot->no_batch }} — {{ $lot->kuantiti }} {{ $produk->unit }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>

                            <button type="button" class="btn-garis btn-ikon shrink-0" data-buang-baris aria-label="{{ __('wky.aksi.padam') }}">
                                <x-ikon nama="silang" kelas="size-4" />
                            </button>
                        </div>
                    @endforeach
                </div>

                @error('baris') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="catatan" class="mb-1 block font-medium">{{ __('wky.medan.catatan') }}</label>
                <textarea id="catatan" name="catatan" rows="2">{{ old('catatan') }}</textarea>
            </div>

            <div class="amaran-info">
                <x-ikon nama="amaran" kelas="size-5 shrink-0" />
                <span>{{ __('wky.jual.nota_kos') }}</span>
            </div>
        </div>

        <div class="kad-kaki">
            <button type="submit" class="btn-utama">{{ __('wky.jual.tambah') }}</button>
            <a href="{{ route('sales.index') }}" class="btn-garis">{{ __('wky.aksi.batal') }}</a>
        </div>
    </form>
@endsection

@push('skrip')
    <script>
        (function () {
            const senarai = document.getElementById('senaraiBaris');

            document.getElementById('tambahBaris').addEventListener('click', function () {
                const baris = senarai.querySelector('[data-baris]').cloneNode(true);
                const nombor = senarai.querySelectorAll('[data-baris]').length;

                // Nama medan dinomborkan semula supaya PHP menerimanya sebagai
                // baris berasingan dan bukan sebagai satu baris yang bertindih.
                baris.querySelectorAll('[name]').forEach(function (medan) {
                    medan.name = medan.name.replace(/baris\[\d+]/, `baris[${nombor}]`);
                    medan.value = '';
                });

                senarai.appendChild(baris);
                segarkan(baris);
            });

            // Baris terakhir tidak dibuang: borang tanpa satu baris pun tidak
            // meninggalkan apa-apa untuk diisi.
            senarai.addEventListener('click', function (peristiwa) {
                const butang = peristiwa.target.closest('[data-buang-baris]');

                if (butang && senarai.querySelectorAll('[data-baris]').length > 1) {
                    butang.closest('[data-baris]').remove();
                }
            });

            function segarkan(baris) {
                const pilih = baris.querySelector('[data-produk]');
                const pilihan = pilih.selectedOptions[0];
                const jejak = Boolean(pilihan?.dataset.jejak);
                const lot = baris.querySelector('[data-lot]');

                // Harga produk sebagai placeholder dan bukan nilai: pengawal
                // sudah jatuh kepada harga itu apabila medan dibiarkan kosong,
                // dan nilai yang ditulis sendiri akan menjadi angka yang
                // pengguna sangka dia sahkan.
                baris.querySelector('input[data-harga]').placeholder = pilihan?.dataset.harga ?? '';

                lot.classList.toggle('hidden', ! jejak);
                lot.disabled = ! jejak;
                lot.required = jejak;

                // Hanya lot milik produk terpilih yang boleh dipilih.
                [...lot.options].forEach(function (pilihanLot) {
                    const padan = ! pilihanLot.value || pilihanLot.dataset.produk === pilih.value;

                    pilihanLot.hidden = ! padan;
                    pilihanLot.disabled = ! padan;
                });

                if (lot.selectedOptions[0]?.disabled) {
                    lot.value = '';
                }
            }

            senarai.addEventListener('change', function (peristiwa) {
                const baris = peristiwa.target.closest('[data-baris]');

                if (baris && peristiwa.target.matches('[data-produk]')) {
                    segarkan(baris);
                }
            });

            senarai.querySelectorAll('[data-baris]').forEach(segarkan);
        })();
    </script>
@endpush
