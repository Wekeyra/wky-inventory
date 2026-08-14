@extends('layouts.app')
@section('tajuk', __('wky.analitik.tajuk'))

@section('kandungan')
    <div class="space-y-6">
        <form method="GET" class="kad kad-badan flex flex-wrap items-center gap-3">
            <label for="hari" class="font-medium">{{ __('wky.analitik.tempoh') }}</label>
            <select id="hari" name="hari" class="!w-auto min-w-44" onchange="this.form.submit()">
                @foreach ($tempohPilihan as $pilihan)
                    <option value="{{ $pilihan }}" @selected($hari === $pilihan)>
                        {{ __('wky.analitik.tempoh_hari', ['hari' => $pilihan]) }}
                    </option>
                @endforeach
            </select>
        </form>

        {{-- ---------- Pusing ganti ---------- --}}
        <div class="kad">
            <div class="kad-kepala">
                <span class="font-semibold">{{ __('wky.analitik.pusing_ganti') }}</span>
            </div>

            <div class="kad-badan grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="label-stat">{{ __('wky.analitik.kadar') }}</p>
                    <p class="nilai-stat mt-1">{{ number_format($pusingGanti['kadar'], 2) }}&times;</p>
                </div>
                <div>
                    <p class="label-stat">{{ __('wky.analitik.hari_stok') }}</p>
                    <p class="nilai-stat mt-1">
                        {{ $pusingGanti['hariStok'] === null ? __('wky.umum.kosong') : number_format($pusingGanti['hariStok'], 0) }}
                    </p>
                </div>
                <div>
                    <p class="label-stat">{{ __('wky.analitik.kos_keluar') }}</p>
                    <p class="nilai-stat mt-1">{{ number_format($pusingGanti['kosKeluar'], 2) }}</p>
                </div>
                <div>
                    <p class="label-stat">{{ __('wky.laporan.nilai_stok_semasa') }}</p>
                    <p class="nilai-stat mt-1">{{ number_format($pusingGanti['nilaiStok'], 2) }}</p>
                </div>
            </div>

            @unless ($pusingGanti['kosLengkap'])
                <div class="kad-badan border-t border-bingkai pt-0">
                    <div class="amaran-gagal">
                        <x-ikon nama="amaran" kelas="size-5 shrink-0" />
                        <span>{{ __('wky.analitik.kos_tidak_lengkap') }}</span>
                    </div>
                </div>
            @endunless

            <div class="kad-kaki block text-xs text-malap">{{ __('wky.analitik.pusing_ganti_nota') }}</div>
        </div>

        {{-- ---------- Reorder ---------- --}}
        {{--
            Borang ini menghantar cadangan yang ditanda ke borang permohonan
            pembelian sebagai ?produk[ID]=KUANTITI, jadi gelung reorder → pesan
            tertutup tanpa menaip semula senarai yang baru sahaja dibaca.
        --}}
        <form method="GET" action="{{ route('purchase-orders.create') }}" class="kad">
            <div class="kad-kepala">
                <span class="font-semibold">{{ __('wky.analitik.reorder') }}</span>

                @if ($reorder->isNotEmpty())
                    <button type="submit" class="btn-utama btn-kecil">
                        <x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.analitik.buat_pesanan') }}
                    </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="jadual">
                    <thead>
                        <tr>
                            <th class="w-10">
                                <input type="checkbox" id="pilihSemua" class="!w-auto"
                                       aria-label="{{ __('wky.analitik.pilih_semua') }}">
                            </th>
                            <th>{{ __('wky.medan.produk') }}</th>
                            <th>{{ __('wky.medan.pembekal') }}</th>
                            <th class="text-right">{{ __('wky.stok.baki') }}</th>
                            <th class="text-right">{{ __('wky.medan.stok_minimum') }}</th>
                            <th class="text-right">{{ __('wky.analitik.digunakan') }}</th>
                            <th class="text-right">{{ __('wky.analitik.sebulan') }}</th>
                            <th class="text-right">{{ __('wky.analitik.cadangan') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($reorder as $baris)
                        <tr>
                            <td>
                                {{-- Nama medan membawa ID produk, dan nilainya kuantiti
                                     cadangan. Kotak yang tidak ditanda langsung tidak
                                     dihantar, jadi tiada penapisan diperlukan di hujung sana. --}}
                                <input type="checkbox" class="!w-auto" data-pilih
                                       name="produk[{{ $baris['produk']->id }}]"
                                       value="{{ $baris['cadangan'] }}"
                                       aria-label="{{ $baris['produk']->nama }}">
                            </td>
                            <td>
                                <a href="{{ route('products.show', $baris['produk']) }}" class="pautan-jadual">
                                    {{ $baris['produk']->nama }}
                                </a>
                                <p class="text-xs text-malap">{{ $baris['produk']->sku }}</p>
                            </td>
                            <td class="text-malap">{{ $baris['produk']->supplier?->nama ?? __('wky.umum.kosong') }}</td>
                            <td class="text-right">
                                <span class="lencana-bahaya">{{ $baris['produk']->stok }}</span>
                            </td>
                            <td class="text-right text-malap">{{ $baris['produk']->stok_minimum }}</td>
                            <td class="text-right text-malap">{{ $baris['digunakan'] }}</td>
                            <td class="text-right text-malap">{{ $baris['sebulan'] }}</td>
                            <td class="text-right font-medium">{{ $baris['cadangan'] }} {{ $baris['produk']->unit }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-10 text-center text-malap">{{ __('wky.analitik.reorder_tiada') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="kad-kaki block text-xs text-malap">{{ __('wky.analitik.reorder_nota') }}</div>
        </form>

        {{-- ---------- Terlaris ---------- --}}
        <div class="kad">
            <div class="kad-kepala">
                <span class="font-semibold">{{ __('wky.analitik.terlaris') }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="jadual">
                    <thead>
                        <tr>
                            <th>{{ __('wky.medan.produk') }}</th>
                            <th class="text-right">{{ __('wky.medan.kuantiti') }}</th>
                            <th class="text-right">{{ __('wky.jual.jumlah_jualan') }}</th>
                            <th class="text-right">{{ __('wky.jual.untung_kasar') }}</th>
                            <th class="text-right">{{ __('wky.jual.margin') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($terlaris as $baris)
                        <tr>
                            <td>
                                <a href="{{ route('products.show', $baris['produk']) }}" class="pautan-jadual">
                                    {{ $baris['produk']->nama }}
                                </a>
                                <p class="text-xs text-malap">{{ $baris['produk']->sku }}</p>
                            </td>
                            <td class="text-right">{{ $baris['kuantiti'] }} {{ $baris['produk']->unit }}</td>
                            <td class="text-right whitespace-nowrap">{{ number_format($baris['jualan'], 2) }}</td>
                            <td class="text-right font-medium whitespace-nowrap">
                                {{ number_format($baris['untung'], 2) }}
                                @unless ($baris['kosPenuh'])
                                    <span class="lencana-kuning ml-1" title="{{ __('wky.jual.kos_tidak_lengkap') }}">?</span>
                                @endunless
                            </td>
                            <td class="text-right text-malap">
                                {{ $baris['jualan'] > 0 ? number_format($baris['untung'] / $baris['jualan'] * 100, 1).'%' : __('wky.umum.kosong') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-malap">{{ __('wky.analitik.terlaris_tiada') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="kad-kaki block text-xs text-malap">{{ __('wky.analitik.terlaris_nota') }}</div>
        </div>

        {{-- ---------- Stok mati ---------- --}}
        <div class="kad">
            <div class="kad-kepala">
                <span class="font-semibold">{{ __('wky.analitik.stok_mati') }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="jadual">
                    <thead>
                        <tr>
                            <th>{{ __('wky.medan.produk') }}</th>
                            <th class="text-right">{{ __('wky.stok.baki') }}</th>
                            <th>{{ __('wky.analitik.keluar_terakhir') }}</th>
                            <th class="text-right">{{ __('wky.analitik.nilai_tersekat') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($stokMati as $baris)
                        <tr>
                            <td>
                                <a href="{{ route('products.show', $baris['produk']) }}" class="pautan-jadual">
                                    {{ $baris['produk']->nama }}
                                </a>
                                <p class="text-xs text-malap">{{ $baris['produk']->sku }}</p>
                            </td>
                            <td class="text-right">{{ $baris['produk']->stok }} {{ $baris['produk']->unit }}</td>
                            <td class="text-malap">
                                {{ $baris['terakhir']?->format('d/m/Y') ?? __('wky.analitik.tidak_pernah') }}
                            </td>
                            <td class="text-right font-medium whitespace-nowrap">{{ number_format($baris['nilai'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-10 text-center text-malap">{{ __('wky.analitik.stok_mati_tiada') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="kad-kaki block text-xs text-malap">
                {{ __('wky.analitik.stok_mati_nota', ['hari' => $hariMati]) }}
            </div>
        </div>
    </div>
@endsection

@push('skrip')
    <script>
        (function () {
            const semua = document.getElementById('pilihSemua');

            if (! semua) {
                return;
            }

            semua.addEventListener('change', function () {
                document.querySelectorAll('[data-pilih]').forEach(function (kotak) {
                    kotak.checked = semua.checked;
                });
            });
        })();
    </script>
@endpush
