@extends('layouts.app')
@section('tajuk', __('wky.laporan.tajuk'))

@section('kandungan')
    <div class="kad kad-badan tanpa-cetak mb-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="min-w-56 flex-1">
                <label for="bulan" class="mb-1 block font-medium">{{ __('wky.laporan.bulan_laporan') }}</label>
                <select id="bulan" name="bulan" onchange="this.form.submit()">
                    @foreach ($pilihanBulan as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($bulan->format('Y-m') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-garis"><x-ikon nama="segar-semula" kelas="size-4" /> {{ __('wky.aksi.papar') }}</button>
                <button type="button" class="btn-wky" onclick="window.print()"><x-ikon nama="pencetak" kelas="size-4" /> {{ __('wky.aksi.cetak') }}</button>
            </div>
        </form>
    </div>

    <div class="mb-4">
        <h2 class="text-lg font-semibold text-teks">{{ __('wky.laporan.tajuk_penuh', ['bulan' => $bulan->translatedFormat('F Y')]) }}</h2>
        <p class="text-sm text-malap">
            {{ __('wky.laporan.dijana_pada', ['tarikh' => now()->format('d/m/Y H:i'), 'nama' => auth()->user()->name]) }}
        </p>
    </div>

    @php
        $ringkasan = [
            [__('wky.laporan.jumlah_masuk'), number_format($jumlahMasuk), 'masuk'],
            [__('wky.laporan.jumlah_keluar'), number_format($jumlahKeluar), 'keluar'],
            [__('wky.laporan.bilangan_transaksi'), number_format($jumlahTransaksi), 'resit'],
            [__('wky.laporan.nilai_stok_semasa'), number_format($nilaiStokSemasa, 2), 'wang'],
        ];
    @endphp

    <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($ringkasan as [$label, $nilai, $ikon])
            <div class="kad kad-badan flex items-center gap-4">
                <div class="ikon-bulat"><x-ikon :nama="$ikon" kelas="size-6" /></div>
                <div class="min-w-0">
                    <p class="label-stat">{{ $label }}</p>
                    <p class="nilai-stat truncate">{{ $nilai }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{--
        Untung kasar hanya dipaparkan apabila ada jualan bulan itu. Kad yang
        menunjukkan RM 0.00 setiap bulan sebelum modul jualan digunakan hanya
        mengajar pengguna mengabaikannya.
    --}}
    @if ($bilJualan > 0)
        <div class="mb-4 grid gap-4 sm:grid-cols-3">
            <div class="kad kad-badan">
                <p class="label-stat">{{ __('wky.jual.jumlah_jualan') }}</p>
                <p class="nilai-stat mt-1 truncate">{{ number_format($jumlahJualan, 2) }}</p>
            </div>
            <div class="kad kad-badan">
                <p class="label-stat">{{ __('wky.jual.kos_barang') }}</p>
                <p class="nilai-stat mt-1 truncate">{{ number_format($kosBarangDijual, 2) }}</p>
            </div>
            <div class="kad kad-badan">
                <p class="label-stat">{{ __('wky.jual.untung_kasar') }}</p>
                <p class="nilai-stat mt-1 truncate">{{ number_format($untungKasar, 2) }}</p>
                @if ($jumlahJualan > 0)
                    <p class="mt-1 text-xs text-malap">
                        {{ __('wky.jual.margin') }}: {{ number_format($untungKasar / $jumlahJualan * 100, 1) }}%
                    </p>
                @endif
            </div>
        </div>

        @if ($kosTidakLengkap)
            <div class="amaran-gagal mb-4">
                <x-ikon nama="amaran" kelas="size-5 shrink-0" />
                <span>{{ __('wky.jual.kos_tidak_lengkap') }}</span>
            </div>
        @endif
    @endif

    <div class="kad">
        <div class="kad-kepala">
            <span class="flex items-center gap-2 font-semibold">
                <x-ikon nama="senarai" kelas="size-5 text-aksen" />
                {{ __('wky.laporan.pecahan_produk') }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.sku') }}</th>
                        <th>{{ __('wky.medan.produk') }}</th>
                        <th class="text-right">{{ __('wky.laporan.masuk') }}</th>
                        <th class="text-right">{{ __('wky.laporan.keluar') }}</th>
                        <th class="text-right">{{ __('wky.laporan.perubahan_bersih') }}</th>
                        <th class="text-right">{{ __('wky.laporan.transaksi') }}</th>
                        <th class="text-right">{{ __('wky.laporan.baki_semasa') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($baris as $item)
                    @php $bersih = $item['masuk'] - $item['keluar']; @endphp
                    <tr>
                        <td><code>{{ $item['produk']->sku }}</code></td>
                        <td>
                            <a href="{{ route('products.show', $item['produk']) }}" class="pautan-jadual">{{ $item['produk']->nama }}</a>
                            @if ($item['pelarasan'] > 0)
                                <span class="lencana-kuning ml-1">{{ __('wky.laporan.bil_pelarasan', ['bil' => $item['pelarasan']]) }}</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($item['masuk']) }}</td>
                        <td class="text-right">{{ number_format($item['keluar']) }}</td>
                        <td class="text-right font-medium {{ $bersih < 0 ? 'text-bahaya-terang' : ($bersih > 0 ? 'text-emerald-400' : 'text-malap') }}">
                            {{ $bersih > 0 ? '+' : '' }}{{ number_format($bersih) }}
                        </td>
                        <td class="text-right text-malap">{{ $item['bil_transaksi'] }}</td>
                        <td class="text-right whitespace-nowrap">{{ number_format($item['produk']->stok) }} {{ $item['produk']->unit }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-malap">
                            {{ __('wky.laporan.tiada_pergerakan', ['bulan' => $bulan->translatedFormat('F Y')]) }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
                @if ($baris->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="2" class="text-right">{{ __('wky.umum.jumlah') }}</td>
                            <td class="text-right">{{ number_format($jumlahMasuk) }}</td>
                            <td class="text-right">{{ number_format($jumlahKeluar) }}</td>
                            <td class="text-right">{{ $jumlahMasuk - $jumlahKeluar > 0 ? '+' : '' }}{{ number_format($jumlahMasuk - $jumlahKeluar) }}</td>
                            <td class="text-right">{{ number_format($jumlahTransaksi) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <p class="mt-4 text-sm text-malap">
        {!! __('wky.laporan.nota', ['bersih' => '<strong class="text-teks">' . e(__('wky.laporan.perubahan_bersih')) . '</strong>']) !!}
    </p>
@endsection
