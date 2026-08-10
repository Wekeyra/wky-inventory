@extends('layouts.app')
@section('tajuk', __('wky.kiraan.tajuk_papar', ['kod' => $sesi->kod]))

@section('kandungan')
    @php
        $dikira = $sesi->items->filter->sudahDikira();
        $berbeza = $dikira->filter(fn ($item) => $item->beza() !== 0);
        $lebih = $dikira->sum(fn ($item) => max($item->beza(), 0));
        $kurang = $dikira->sum(fn ($item) => min($item->beza(), 0));
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-{{ $sesi->warnaStatus() }}">{{ $sesi->labelStatus() }}</span>
                        <code>{{ $sesi->kod }}</code>
                    </div>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-3">{{ __('wky.kiraan.skop') }}</dt>
                        <dd class="col-sm-9">{{ $sesi->category?->nama ?? __('wky.umum.semua_kategori') }}</dd>

                        <dt class="col-sm-3">{{ __('wky.dashboard.dibuka_oleh') }}</dt>
                        <dd class="col-sm-9">
                            {{ __('wky.kiraan.dibuka_oleh_pada', [
                                'nama' => $sesi->pembuka?->name ?? __('wky.umum.kosong'),
                                'tarikh' => $sesi->created_at->format('d/m/Y H:i'),
                            ]) }}
                        </dd>

                        @if ($sesi->disahkan_pada)
                            <dt class="col-sm-3">{{ __('wky.kiraan.disahkan_oleh') }}</dt>
                            <dd class="col-sm-9">
                                {{ __('wky.kiraan.dibuka_oleh_pada', [
                                    'nama' => $sesi->pengesah?->name ?? __('wky.umum.kosong'),
                                    'tarikh' => $sesi->disahkan_pada->format('d/m/Y H:i'),
                                ]) }}
                            </dd>
                        @endif

                        @if ($sesi->catatan)
                            <dt class="col-sm-3">{{ __('wky.medan.catatan') }}</dt>
                            <dd class="col-sm-9 text-secondary">{{ $sesi->catatan }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small">{{ __('wky.kiraan.sudah_dikira') }}</span>
                        <span class="fw-semibold"><span id="bilDikira">{{ $dikira->count() }}</span> / {{ $sesi->items->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small">{{ __('wky.kiraan.produk_berbeza') }}</span>
                        <span class="fw-semibold text-warning" id="bilBerbeza">{{ $berbeza->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small">{{ __('wky.kiraan.jumlah_lebih') }}</span>
                        <span class="fw-semibold text-success" id="jumlahLebih">+{{ number_format($lebih) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary small">{{ __('wky.kiraan.jumlah_kurang') }}</span>
                        <span class="fw-semibold text-danger" id="jumlahKurang">{{ number_format($kurang) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('stock-counts.update', $sesi) }}" id="borangKiraan">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-list-check me-1"></i>{{ __('wky.kiraan.senarai_produk') }}</span>
                @if ($sesi->isDraf())
                    <span class="small text-secondary">{{ __('wky.kiraan.biarkan_kosong') }}</span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('wky.medan.sku') }}</th>
                            <th>{{ __('wky.medan.produk') }}</th>
                            <th>{{ __('wky.medan.kategori') }}</th>
                            <th class="text-end">{{ __('wky.kiraan.kuantiti_rekod') }}</th>
                            <th class="text-end" style="width: 9rem;">{{ __('wky.kiraan.kuantiti_fizikal') }}</th>
                            <th class="text-end">{{ __('wky.kiraan.beza') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($sesi->items as $item)
                        @php $beza = $item->beza(); @endphp
                        <tr>
                            <td><code>{{ $item->product?->sku ?? __('wky.umum.kosong') }}</code></td>
                            <td>{{ $item->product?->nama ?? __('wky.kiraan.produk_dipadam') }}</td>
                            <td class="small text-secondary">{{ $item->product?->category?->nama ?? __('wky.umum.kosong') }}</td>
                            <td class="text-end" data-rekod>{{ number_format($item->kuantiti_rekod) }}</td>
                            <td class="text-end">
                                @if ($sesi->isDraf())
                                    <input class="form-control form-control-sm text-end"
                                           type="number" min="0" inputmode="numeric"
                                           name="kuantiti[{{ $item->id }}]"
                                           value="{{ old("kuantiti.{$item->id}", $item->kuantiti_fizikal) }}"
                                           data-rekod-nilai="{{ $item->kuantiti_rekod }}"
                                           aria-label="{{ __('wky.kiraan.kuantiti_fizikal') }}">
                                @else
                                    {{ $item->sudahDikira() ? number_format($item->kuantiti_fizikal) : __('wky.umum.kosong') }}
                                @endif
                            </td>
                            <td class="text-end fw-medium" data-beza>
                                @if ($beza === null)
                                    <span class="text-secondary">{{ __('wky.umum.kosong') }}</span>
                                @else
                                    <span class="{{ $beza < 0 ? 'text-danger' : ($beza > 0 ? 'text-success' : 'text-secondary') }}">
                                        {{ $beza > 0 ? '+' : '' }}{{ number_format($beza) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if ($sesi->isDraf())
                <div class="card-footer d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-save me-1"></i>{{ __('wky.kiraan.simpan_draf') }}</button>
                    <a class="btn btn-outline-secondary ms-auto" href="{{ route('stock-counts.index') }}">{{ __('wky.aksi.kembali') }}</a>
                </div>
            @else
                <div class="card-footer">
                    <a class="btn btn-outline-secondary" href="{{ route('stock-counts.index') }}">{{ __('wky.aksi.kembali') }}</a>
                </div>
            @endif
        </div>
    </form>

    @if ($sesi->isDraf())
        <div class="d-flex flex-wrap gap-2 mt-3">
            <form method="POST" action="{{ route('stock-counts.confirm', $sesi) }}"
                  onsubmit="return confirm('{{ __('wky.kiraan.sahkan_confirm', ['kod' => $sesi->kod]) }}')">
                @csrf
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1"></i>{{ __('wky.kiraan.sahkan_laraskan') }}</button>
            </form>

            <form method="POST" action="{{ route('stock-counts.destroy', $sesi) }}"
                  onsubmit="return confirm('{{ __('wky.kiraan.batal_confirm', ['kod' => $sesi->kod]) }}')">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger" type="submit"><i class="bi bi-x-circle me-1"></i>{{ __('wky.kiraan.batalkan_sesi') }}</button>
            </form>
        </div>

        <p class="small text-secondary mt-3 mb-0">
            {!! __('wky.kiraan.nota_pengesahan', [
                'pelarasan' => '<strong>' . e(__('wky.stok.pelarasan')) . '</strong>',
                'kod' => '<code>' . e($sesi->kod) . '</code>',
            ]) !!}
        </p>
    @endif
@endsection

@if ($sesi->isDraf())
    @push('skrip')
        <script>
            // Mengira beza secara langsung semasa staf menaip, tanpa menunggu simpanan.
            (function () {
                const borang = document.getElementById('borangKiraan');
                const input = borang.querySelectorAll('input[data-rekod-nilai]');
                const kosong = @json(__('wky.umum.kosong'));

                function segarkan() {
                    let dikira = 0, berbeza = 0, lebih = 0, kurang = 0;

                    input.forEach(function (medan) {
                        const sel = medan.closest('tr').querySelector('[data-beza]');
                        const rekod = parseInt(medan.dataset.rekodNilai, 10);

                        if (medan.value === '') {
                            sel.innerHTML = '<span class="text-secondary">' + kosong + '</span>';
                            return;
                        }

                        const beza = parseInt(medan.value, 10) - rekod;
                        dikira++;

                        if (beza !== 0) {
                            berbeza++;
                            beza > 0 ? lebih += beza : kurang += beza;
                        }

                        const warna = beza < 0 ? 'text-danger' : (beza > 0 ? 'text-success' : 'text-secondary');
                        sel.innerHTML = '<span class="' + warna + '">' + (beza > 0 ? '+' : '') + beza + '</span>';
                    });

                    document.getElementById('bilDikira').textContent = dikira;
                    document.getElementById('bilBerbeza').textContent = berbeza;
                    document.getElementById('jumlahLebih').textContent = '+' + lebih;
                    document.getElementById('jumlahKurang').textContent = kurang;
                }

                input.forEach(function (medan) {
                    medan.addEventListener('input', segarkan);
                });
            })();
        </script>
    @endpush
@endif
