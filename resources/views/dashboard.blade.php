@extends('layouts.app')
@section('tajuk', __('wky.nav.dashboard'))

@section('kandungan')
    <div class="row g-3 mb-3">
        @php
            $stat = [
                [__('wky.dashboard.jumlah_produk'), number_format($jumlahProduk), 'bi-box'],
                [__('wky.dashboard.kategori'), number_format($jumlahKategori), 'bi-tags'],
                [__('wky.dashboard.pembekal'), number_format($jumlahPembekal), 'bi-truck'],
                [__('wky.dashboard.nilai_stok'), number_format($nilaiStok, 2), 'bi-cash-stack'],
            ];
        @endphp
        @foreach ($stat as [$label, $nilai, $ikon])
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

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="row g-3 mb-3 tanpa-cetak">
                <div class="col-sm-6">
                    <button class="btn btn-wky w-100 py-2" data-bs-toggle="modal" data-bs-target="#modalStokPantas">
                        <i class="bi bi-plus-circle me-1"></i>{{ __('wky.dashboard.tambah_stok_pantas') }}
                    </button>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-wky w-100 py-2" href="{{ route('reports.monthly') }}">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i>{{ __('wky.dashboard.laporan_bulanan') }}
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-exclamation-triangle text-danger me-1"></i>{{ __('wky.dashboard.amaran_stok_rendah') }}</span>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('products.index', ['stok_rendah' => 1]) }}">{{ __('wky.aksi.lihat_semua') }}</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr><th>{{ __('wky.medan.produk') }}</th><th class="text-end">{{ __('wky.medan.stok') }}</th><th class="text-end">{{ __('wky.dashboard.minimum') }}</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($stokRendah as $produk)
                            <tr>
                                <td>
                                    <a href="{{ route('products.show', $produk) }}" class="text-decoration-none">{{ $produk->nama }}</a>
                                    <div class="small text-secondary">{{ $produk->sku }}</div>
                                </td>
                                <td class="text-end"><span class="badge bg-danger">{{ $produk->stok }}</span></td>
                                <td class="text-end text-secondary">{{ $produk->stok_minimum }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-secondary py-4">{{ __('wky.dashboard.tiada_stok_kritikal') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">
                        <i class="bi bi-clipboard-check me-1" style="color: var(--wky-merah);"></i>{{ __('wky.kiraan.tajuk') }}
                        @if ($kiraanDraf > 0)
                            <span class="badge bg-warning text-dark ms-1">{{ __('wky.dashboard.sesi_terbuka', ['bil' => $kiraanDraf]) }}</span>
                        @endif
                    </span>
                    <div class="d-flex gap-2 tanpa-cetak">
                        <a class="btn btn-sm btn-primary" href="{{ route('stock-counts.create') }}"><i class="bi bi-plus-lg"></i> {{ __('wky.dashboard.sesi_baru') }}</a>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('stock-counts.index') }}">{{ __('wky.aksi.lihat_semua') }}</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('wky.medan.kod') }}</th>
                                <th>{{ __('wky.medan.status') }}</th>
                                <th class="text-end">{{ __('wky.dashboard.progres') }}</th>
                                <th>{{ __('wky.dashboard.dibuka_oleh') }}</th>
                                <th>{{ __('wky.medan.tarikh') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($sesiKiraan as $sesi)
                            <tr>
                                <td><a class="text-decoration-none" href="{{ route('stock-counts.show', $sesi) }}"><code>{{ $sesi->kod }}</code></a></td>
                                <td><span class="badge bg-{{ $sesi->warnaStatus() }}">{{ $sesi->labelStatus() }}</span></td>
                                <td class="text-end">{{ $sesi->items_dikira_count }} / {{ $sesi->items_count }}</td>
                                <td class="small">{{ $sesi->pembuka?->name ?? __('wky.umum.kosong') }}</td>
                                <td class="small text-secondary text-nowrap">{{ $sesi->created_at->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary py-4">{{ __('wky.dashboard.tiada_sesi_kiraan') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="fw-semibold"><i class="bi bi-graph-up me-1"></i>{{ __('wky.dashboard.ringkasan_bulanan') }}</div>
                    <div class="small text-secondary">{{ __('wky.dashboard.ringkasan_subtajuk') }}</div>
                </div>
                <div class="card-body">
                    <canvas id="cartaRingkasan" height="150"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-clock-history me-1"></i>{{ __('wky.dashboard.pergerakan_terkini') }}</span>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('stock.index') }}">{{ __('wky.aksi.lihat_semua') }}</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('wky.medan.produk') }}</th>
                                <th>{{ __('wky.medan.jenis') }}</th>
                                <th class="text-end">{{ __('wky.medan.kuantiti') }}</th>
                                <th>{{ __('wky.medan.tarikh') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($pergerakanTerkini as $gerak)
                            <tr>
                                <td>{{ $gerak->product?->nama ?? __('wky.umum.kosong') }}</td>
                                <td>
                                    <span class="badge bg-{{ ['masuk' => 'danger', 'keluar' => 'secondary'][$gerak->jenis] ?? 'warning' }}">
                                        {{ $gerak->labelJenis() }}
                                    </span>
                                </td>
                                <td class="text-end">{{ $gerak->kuantiti }}</td>
                                <td class="small text-secondary text-nowrap">{{ $gerak->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-secondary py-4">{{ __('wky.dashboard.tiada_pergerakan') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('stock.modal-pantas', ['products' => $produkAktif])
@endsection

@push('skrip')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const kanvas = document.getElementById('cartaRingkasan');
            const data = @json($ringkasanBulanan);

            const kecerunan = (ctx, warna) => {
                const g = ctx.createLinearGradient(0, 0, 0, 180);
                g.addColorStop(0, warna.replace(')', ', 0.45)').replace('rgb', 'rgba'));
                g.addColorStop(1, warna.replace(')', ', 0)').replace('rgb', 'rgba'));
                return g;
            };

            new Chart(kanvas, {
                type: 'line',
                data: {
                    labels: data.label,
                    datasets: [
                        {
                            label: @json(__('wky.dashboard.kemasukan')),
                            data: data.masuk,
                            borderColor: 'rgb(220, 38, 38)',
                            backgroundColor: kecerunan(kanvas.getContext('2d'), 'rgb(220, 38, 38)'),
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                        },
                        {
                            label: @json(__('wky.dashboard.pengeluaran')),
                            data: data.keluar,
                            borderColor: 'rgb(148, 163, 184)',
                            backgroundColor: kecerunan(kanvas.getContext('2d'), 'rgb(148, 163, 184)'),
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: '#9096a1', boxWidth: 12, font: { size: 11 } } },
                    },
                    scales: {
                        x: { ticks: { color: '#9096a1', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' } },
                        y: { beginAtZero: true, ticks: { color: '#9096a1', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' } },
                    },
                },
            });
        })();
    </script>
@endpush
