@extends('layouts.app')
@section('tajuk', __('wky.laporan.tajuk'))

@section('kandungan')
    <div class="card mb-3 tanpa-cetak">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="GET">
                <div class="col-md-4">
                    <label class="form-label" for="bulan">{{ __('wky.laporan.bulan_laporan') }}</label>
                    <select class="form-select" id="bulan" name="bulan" onchange="this.form.submit()">
                        @foreach ($pilihanBulan as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($bulan->format('Y-m') === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8 d-flex gap-2 justify-content-md-end">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-arrow-repeat me-1"></i>{{ __('wky.aksi.papar') }}</button>
                    <button class="btn btn-wky" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>{{ __('wky.aksi.cetak') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mb-3">
        <h2 class="h5 mb-1 text-white">{{ __('wky.laporan.tajuk_penuh', ['bulan' => $bulan->translatedFormat('F Y')]) }}</h2>
        <p class="small text-secondary mb-0">
            {{ __('wky.laporan.dijana_pada', ['tarikh' => now()->format('d/m/Y H:i'), 'nama' => auth()->user()->name]) }}
        </p>
    </div>

    <div class="row g-3 mb-3">
        @php
            $ringkasan = [
                [__('wky.laporan.jumlah_masuk'), number_format($jumlahMasuk), 'bi-box-arrow-in-down'],
                [__('wky.laporan.jumlah_keluar'), number_format($jumlahKeluar), 'bi-box-arrow-up'],
                [__('wky.laporan.bilangan_transaksi'), number_format($jumlahTransaksi), 'bi-receipt'],
                [__('wky.laporan.nilai_stok_semasa'), number_format($nilaiStokSemasa, 2), 'bi-cash-stack'],
            ];
        @endphp
        @foreach ($ringkasan as [$label, $nilai, $ikon])
            <div class="col-sm-6 col-xl-3">
                <div class="card kad-stat h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="ikon-bulat"><i class="bi {{ $ikon }} fs-5"></i></div>
                        <div>
                            <div class="label-stat">{{ $label }}</div>
                            <div class="nilai-stat">{{ $nilai }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header fw-semibold"><i class="bi bi-table me-1"></i>{{ __('wky.laporan.pecahan_produk') }}</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.sku') }}</th>
                        <th>{{ __('wky.medan.produk') }}</th>
                        <th class="text-end">{{ __('wky.laporan.masuk') }}</th>
                        <th class="text-end">{{ __('wky.laporan.keluar') }}</th>
                        <th class="text-end">{{ __('wky.laporan.perubahan_bersih') }}</th>
                        <th class="text-end">{{ __('wky.laporan.transaksi') }}</th>
                        <th class="text-end">{{ __('wky.laporan.baki_semasa') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($baris as $item)
                    @php $bersih = $item['masuk'] - $item['keluar']; @endphp
                    <tr>
                        <td><code>{{ $item['produk']->sku }}</code></td>
                        <td>
                            <a class="text-decoration-none" href="{{ route('products.show', $item['produk']) }}">{{ $item['produk']->nama }}</a>
                            @if ($item['pelarasan'] > 0)
                                <span class="badge bg-warning text-dark ms-1">{{ __('wky.laporan.bil_pelarasan', ['bil' => $item['pelarasan']]) }}</span>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format($item['masuk']) }}</td>
                        <td class="text-end">{{ number_format($item['keluar']) }}</td>
                        <td class="text-end fw-medium {{ $bersih < 0 ? 'text-danger' : ($bersih > 0 ? 'text-success' : 'text-secondary') }}">
                            {{ $bersih > 0 ? '+' : '' }}{{ number_format($bersih) }}
                        </td>
                        <td class="text-end text-secondary">{{ $item['bil_transaksi'] }}</td>
                        <td class="text-end">{{ number_format($item['produk']->stok) }} {{ $item['produk']->unit }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">
                            {{ __('wky.laporan.tiada_pergerakan', ['bulan' => $bulan->translatedFormat('F Y')]) }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
                @if ($baris->isNotEmpty())
                    <tfoot>
                        <tr class="fw-semibold">
                            <td colspan="2" class="text-end">{{ __('wky.umum.jumlah') }}</td>
                            <td class="text-end">{{ number_format($jumlahMasuk) }}</td>
                            <td class="text-end">{{ number_format($jumlahKeluar) }}</td>
                            <td class="text-end">{{ $jumlahMasuk - $jumlahKeluar > 0 ? '+' : '' }}{{ number_format($jumlahMasuk - $jumlahKeluar) }}</td>
                            <td class="text-end">{{ number_format($jumlahTransaksi) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <p class="small text-secondary mt-3">
        {!! __('wky.laporan.nota', ['bersih' => '<strong>' . e(__('wky.laporan.perubahan_bersih')) . '</strong>']) !!}
    </p>
@endsection
