@extends('layouts.app')
@section('tajuk', 'Kiraan Stok ' . $sesi->kod)

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
                        <dt class="col-sm-3">Skop</dt><dd class="col-sm-9">{{ $sesi->category?->nama ?? 'Semua kategori' }}</dd>
                        <dt class="col-sm-3">Dibuka oleh</dt><dd class="col-sm-9">{{ $sesi->pembuka?->name ?? '—' }} pada {{ $sesi->created_at->format('d/m/Y H:i') }}</dd>
                        @if ($sesi->disahkan_pada)
                            <dt class="col-sm-3">Disahkan oleh</dt><dd class="col-sm-9">{{ $sesi->pengesah?->name ?? '—' }} pada {{ $sesi->disahkan_pada->format('d/m/Y H:i') }}</dd>
                        @endif
                        @if ($sesi->catatan)
                            <dt class="col-sm-3">Catatan</dt><dd class="col-sm-9 text-secondary">{{ $sesi->catatan }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small">Sudah dikira</span>
                        <span class="fw-semibold"><span id="bilDikira">{{ $dikira->count() }}</span> / {{ $sesi->items->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small">Produk berbeza</span>
                        <span class="fw-semibold text-warning" id="bilBerbeza">{{ $berbeza->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small">Jumlah lebih</span>
                        <span class="fw-semibold text-success" id="jumlahLebih">+{{ number_format($lebih) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary small">Jumlah kurang</span>
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
                <span class="fw-semibold"><i class="bi bi-list-check me-1"></i>Senarai Produk</span>
                @if ($sesi->isDraf())
                    <span class="small text-secondary">Biarkan kosong untuk produk yang belum dikira</span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th class="text-end">Kuantiti Rekod</th>
                            <th class="text-end" style="width: 9rem;">Kuantiti Fizikal</th>
                            <th class="text-end">Beza</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($sesi->items as $item)
                        @php $beza = $item->beza(); @endphp
                        <tr>
                            <td><code>{{ $item->product?->sku ?? '—' }}</code></td>
                            <td>{{ $item->product?->nama ?? 'Produk telah dipadam' }}</td>
                            <td class="small text-secondary">{{ $item->product?->category?->nama ?? '—' }}</td>
                            <td class="text-end" data-rekod>{{ number_format($item->kuantiti_rekod) }}</td>
                            <td class="text-end">
                                @if ($sesi->isDraf())
                                    <input class="form-control form-control-sm text-end"
                                           type="number" min="0" inputmode="numeric"
                                           name="kuantiti[{{ $item->id }}]"
                                           value="{{ old("kuantiti.{$item->id}", $item->kuantiti_fizikal) }}"
                                           data-rekod-nilai="{{ $item->kuantiti_rekod }}">
                                @else
                                    {{ $item->sudahDikira() ? number_format($item->kuantiti_fizikal) : '—' }}
                                @endif
                            </td>
                            <td class="text-end fw-medium" data-beza>
                                @if ($beza === null)
                                    <span class="text-secondary">—</span>
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
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-save me-1"></i>Simpan Draf</button>
                    <a class="btn btn-outline-secondary ms-auto" href="{{ route('stock-counts.index') }}">Kembali</a>
                </div>
            @else
                <div class="card-footer">
                    <a class="btn btn-outline-secondary" href="{{ route('stock-counts.index') }}">Kembali</a>
                </div>
            @endif
        </div>
    </form>

    @if ($sesi->isDraf())
        <div class="d-flex flex-wrap gap-2 mt-3">
            <form method="POST" action="{{ route('stock-counts.confirm', $sesi) }}"
                  onsubmit="return confirm('Sahkan sesi {{ $sesi->kod }}? Stok setiap produk yang berbeza akan dilaraskan kepada kuantiti fizikal dan tindakan ini tidak boleh dibatalkan.')">
                @csrf
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1"></i>Sahkan &amp; Laraskan Stok</button>
            </form>

            <form method="POST" action="{{ route('stock-counts.destroy', $sesi) }}"
                  onsubmit="return confirm('Batalkan sesi {{ $sesi->kod }}? Tiada stok akan dilaraskan.')">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger" type="submit"><i class="bi bi-x-circle me-1"></i>Batalkan Sesi</button>
            </form>
        </div>

        <p class="small text-secondary mt-3 mb-0">
            Pengesahan menjana satu pergerakan stok jenis <strong>pelarasan</strong> bagi setiap produk yang berbeza,
            dengan rujukan <code>{{ $sesi->kod }}</code>. Produk yang kuantiti fizikalnya dibiarkan kosong tidak akan disentuh.
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

                function segarkan() {
                    let dikira = 0, berbeza = 0, lebih = 0, kurang = 0;

                    input.forEach(function (medan) {
                        const sel = medan.closest('tr').querySelector('[data-beza]');
                        const rekod = parseInt(medan.dataset.rekodNilai, 10);

                        if (medan.value === '') {
                            sel.innerHTML = '<span class="text-secondary">&mdash;</span>';
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
