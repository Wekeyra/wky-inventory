@extends('layouts.app')
@section('tajuk', $pesanan->exists ? __('wky.po.tajuk_sunting', ['kod' => $pesanan->kod]) : __('wky.po.tambah'))

@section('kandungan')
    <form method="POST" action="{{ $pesanan->exists ? route('purchase-orders.update', $pesanan) : route('purchase-orders.store') }}"
          class="kad max-w-3xl">
        @csrf
        @if ($pesanan->exists) @method('PUT') @endif

        <div class="kad-badan space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="supplier_id" class="mb-1 block font-medium">{{ __('wky.po.pembekal') }}</label>
                    <select id="supplier_id" name="supplier_id">
                        <option value="">{{ __('wky.umum.kosong') }}</option>
                        @foreach ($suppliers as $pembekal)
                            <option value="{{ $pembekal->id }}" @selected(old('supplier_id', $pesanan->supplier_id) == $pembekal->id)>
                                {{ $pembekal->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tarikh_diperlukan" class="mb-1 block font-medium">{{ __('wky.po.tarikh_diperlukan') }}</label>
                    <input type="date" id="tarikh_diperlukan" name="tarikh_diperlukan"
                           value="{{ old('tarikh_diperlukan', $pesanan->tarikh_diperlukan?->format('Y-m-d')) }}">
                    @error('tarikh_diperlukan') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between">
                    <span class="font-medium">{{ __('wky.po.barang') }} <span class="text-bahaya">*</span></span>
                    <button type="button" class="btn-garis btn-kecil" id="tambahBaris">
                        <x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.po.tambah_baris') }}
                    </button>
                </div>

                <div class="space-y-2" id="senaraiBaris">
                    {{--
                        Baris sedia ada pada draf yang disunting menjadi nilai
                        lalai. old() menang di atasnya, supaya percubaan yang
                        gagal pengesahan memaparkan semula apa yang ditaip dan
                        bukan apa yang tersimpan.
                    --}}
                    @php($lama = old('baris', $pesanan->exists
                        ? $pesanan->items->map(fn ($item) => [
                            'product_id' => $item->product_id,
                            'kuantiti' => $item->kuantiti,
                            'kos_seunit' => $item->kos_seunit,
                        ])->all()
                        // Cadangan reorder daripada halaman Analitik tiba sebagai
                        // ?produk[ID]=KUANTITI dan menjadi baris permulaan.
                        : ($awal ?: [['product_id' => '', 'kuantiti' => '', 'kos_seunit' => '']])))

                    @foreach ($lama as $i => $baris)
                        <div class="flex gap-2" data-baris>
                            <select name="baris[{{ $i }}][product_id]" data-produk class="min-w-0 flex-1">
                                <option value="">{{ __('wky.umum.pilih_produk') }}</option>
                                @foreach ($products as $produk)
                                    {{-- Harga kos dibawa sebagai data-* supaya medan kos boleh
                                         menunjukkan lalainya tanpa memuat semula halaman. --}}
                                    <option value="{{ $produk->id }}" @selected(($baris['product_id'] ?? '') == $produk->id)
                                            data-kos="{{ $produk->harga_kos }}">
                                        {{ $produk->nama }} ({{ $produk->sku }})
                                    </option>
                                @endforeach
                            </select>

                            <input type="number" min="1" name="baris[{{ $i }}][kuantiti]" value="{{ $baris['kuantiti'] ?? '' }}"
                                   placeholder="{{ __('wky.medan.kuantiti') }}" class="!w-24">

                            <input type="number" step="0.01" min="0" name="baris[{{ $i }}][kos_seunit]"
                                   value="{{ $baris['kos_seunit'] ?? '' }}" data-kos
                                   placeholder="{{ __('wky.medan.kos_seunit') }}" class="!w-32">

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
                <textarea id="catatan" name="catatan" rows="2">{{ old('catatan', $pesanan->catatan) }}</textarea>
            </div>

            <div class="amaran-info">
                <span>{{ __('wky.po.nota_draf') }}</span>
            </div>
        </div>

        <div class="kad-kaki">
            <button type="submit" class="btn-utama">{{ __('wky.aksi.simpan') }}</button>
            <a href="{{ $pesanan->exists ? route('purchase-orders.show', $pesanan) : route('purchase-orders.index') }}"
               class="btn-garis">{{ __('wky.aksi.batal') }}</a>
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

            // Harga kos produk ditunjukkan sebagai placeholder dan bukan ditulis
            // sebagai nilai: pengawal sudah jatuh kepada harga itu apabila medan
            // dibiarkan kosong, dan nilai yang ditulis sendiri akan menjadi
            // angka yang pengguna sangka dia sahkan.
            senarai.addEventListener('change', function (peristiwa) {
                const pilih = peristiwa.target.closest('[data-produk]');

                if (! pilih) {
                    return;
                }

                const medan = pilih.closest('[data-baris]').querySelector('input[data-kos]');

                medan.placeholder = pilih.selectedOptions[0]?.dataset.kos ?? '';
            });
        })();
    </script>
@endpush
