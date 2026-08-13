@extends('layouts.app')
@section('tajuk', __('wky.pindah.tambah'))

@section('kandungan')
    <form method="POST" action="{{ route('transfers.store') }}" class="kad max-w-3xl" id="borangPindah">
        @csrf

        <div class="kad-badan space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="location_asal_id" class="mb-1 block font-medium">{{ __('wky.pindah.dari') }} <span class="text-bahaya">*</span></label>
                    <select id="location_asal_id" name="location_asal_id" required @error('location_asal_id') class="medan-ralat" @enderror>
                        @foreach ($locations as $lokasi)
                            <option value="{{ $lokasi->id }}" @selected(old('location_asal_id', $asalTerpilih) == $lokasi->id)>{{ $lokasi->nama }}</option>
                        @endforeach
                    </select>
                    @error('location_asal_id') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="location_tujuan_id" class="mb-1 block font-medium">{{ __('wky.pindah.ke') }} <span class="text-bahaya">*</span></label>
                    <select id="location_tujuan_id" name="location_tujuan_id" required @error('location_tujuan_id') class="medan-ralat" @enderror>
                        @foreach ($locations as $lokasi)
                            <option value="{{ $lokasi->id }}" @selected(old('location_tujuan_id') == $lokasi->id)>{{ $lokasi->nama }}</option>
                        @endforeach
                    </select>
                    @error('location_tujuan_id') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between">
                    <span class="font-medium">{{ __('wky.pindah.barang') }} <span class="text-bahaya">*</span></span>
                    <button type="button" class="btn-garis btn-kecil" id="tambahBaris">
                        <x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.pindah.tambah_baris') }}
                    </button>
                </div>

                <div class="space-y-2" id="senaraiBaris">
                    @php($lama = old('baris', [['product_id' => '', 'kuantiti' => '']]))
                    @foreach ($lama as $i => $baris)
                        <div class="flex gap-2" data-baris>
                            <select name="baris[{{ $i }}][product_id]" data-produk class="flex-1">
                                <option value="">{{ __('wky.umum.pilih_produk') }}</option>
                                @foreach ($products as $produk)
                                    {{-- Baki setiap gudang dibawa sebagai data-* supaya baki gudang asal dapat dipaparkan tanpa memuat semula halaman. --}}
                                    <option value="{{ $produk->id }}" @selected(($baris['product_id'] ?? '') == $produk->id)
                                            data-baki="{{ $produk->balances->pluck('kuantiti', 'location_id')->toJson() }}"
                                            data-unit="{{ $produk->unit }}">
                                        {{ $produk->nama }} ({{ $produk->sku }})
                                    </option>
                                @endforeach
                            </select>

                            <input type="number" min="1" name="baris[{{ $i }}][kuantiti]" value="{{ $baris['kuantiti'] ?? '' }}"
                                   placeholder="{{ __('wky.medan.kuantiti') }}" class="!w-28">

                            <span class="flex min-w-24 items-center text-xs text-malap" data-baki-asal></span>

                            <button type="button" class="btn-garis btn-ikon" data-buang-baris aria-label="{{ __('wky.aksi.padam') }}">
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
                <span>{{ __('wky.pindah.nota_hantar') }}</span>
            </div>
        </div>

        <div class="kad-kaki">
            <button type="submit" class="btn-utama">{{ __('wky.pindah.hantar') }}</button>
            <a href="{{ route('transfers.index') }}" class="btn-garis">{{ __('wky.aksi.batal') }}</a>
        </div>
    </form>
@endsection

@push('skrip')
    <script>
        (function () {
            const senarai = document.getElementById('senaraiBaris');
            const asal = document.getElementById('location_asal_id');
            const teksBaki = @json(__('wky.pindah.baki_asal'));

            document.getElementById('tambahBaris').addEventListener('click', function () {
                const baris = senarai.querySelector('[data-baris]').cloneNode(true);
                const nombor = senarai.querySelectorAll('[data-baris]').length;

                // Nama medan dinomborkan semula supaya PHP menerimanya sebagai
                // baris berasingan dan bukan sebagai satu baris yang bertindih.
                baris.querySelectorAll('[name]').forEach(function (medan) {
                    medan.name = medan.name.replace(/baris\[\d+]/, `baris[${nombor}]`);
                    medan.value = '';
                });

                baris.querySelector('[data-baki-asal]').textContent = '';
                senarai.appendChild(baris);
            });

            // Baris terakhir tidak dibuang: borang tanpa satu baris pun tidak
            // meninggalkan apa-apa untuk diisi, dan butang tambah pula berada
            // jauh di atas senarai.
            senarai.addEventListener('click', function (peristiwa) {
                const butang = peristiwa.target.closest('[data-buang-baris]');

                if (butang && senarai.querySelectorAll('[data-baris]').length > 1) {
                    butang.closest('[data-baris]').remove();
                }
            });

            function segarkanBaki() {
                senarai.querySelectorAll('[data-baris]').forEach(function (baris) {
                    const pilihan = baris.querySelector('[data-produk]').selectedOptions[0];
                    const papar = baris.querySelector('[data-baki-asal]');

                    if (! pilihan || ! pilihan.value) {
                        papar.textContent = '';

                        return;
                    }

                    const baki = JSON.parse(pilihan.dataset.baki || '{}')[asal.value] ?? 0;

                    papar.textContent = teksBaki.replace(':baki', baki).replace(':unit', pilihan.dataset.unit || '');
                });
            }

            senarai.addEventListener('change', segarkanBaki);
            asal.addEventListener('change', segarkanBaki);
            segarkanBaki();
        })();
    </script>
@endpush
