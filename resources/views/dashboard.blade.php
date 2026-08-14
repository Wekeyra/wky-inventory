@extends('layouts.app')
@section('tajuk', __('wky.nav.dashboard'))

@section('kandungan')
    @php
        $stat = [
            [__('wky.dashboard.jumlah_produk'), number_format($jumlahProduk), 'kotak'],
            [__('wky.dashboard.kategori'), number_format($jumlahKategori), 'tag'],
            [__('wky.dashboard.pembekal'), number_format($jumlahPembekal), 'trak'],
            [__('wky.dashboard.nilai_stok'), number_format($nilaiStok, 2), 'wang'],
        ];
    @endphp

    <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stat as [$label, $nilai, $ikon])
            <div class="kad kad-badan flex items-center gap-4">
                <div class="ikon-bulat"><x-ikon :nama="$ikon" kelas="size-6" /></div>
                <div class="min-w-0">
                    <p class="label-stat">{{ $label }}</p>
                    <p class="nilai-stat truncate">{{ $nilai }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-12">
        <div class="space-y-4 xl:col-span-7">
            {{-- Grid mengecil kepada dua lajur apabila imbas invois dimatikan,
                 supaya dua butang yang tinggal tidak meninggalkan lubang. --}}
            <div class="tanpa-cetak grid gap-4 {{ auth()->user()->workspace?->adaCiri('imbas') ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }}">
                @if (auth()->user()->workspace?->adaCiri('imbas'))
                    <a href="{{ route('invoice-scans.create') }}" class="btn-wky py-3">
                        <x-ikon nama="imbas" />
                        {{ __('wky.imbas.butang') }}
                    </a>
                @endif
                <button type="button" class="btn-wky py-3" data-modal-buka="modal-stok-pantas">
                    <x-ikon nama="tambah-bulat" />
                    {{ __('wky.dashboard.tambah_stok_pantas') }}
                </button>
                <a href="{{ route('reports.monthly') }}" class="btn-wky py-3">
                    <x-ikon nama="dokumen-carta" />
                    {{ __('wky.dashboard.laporan_bulanan') }}
                </a>
            </div>

            <div class="kad">
                <div class="kad-kepala">
                    <span class="flex items-center gap-2 font-semibold">
                        <x-ikon nama="amaran" kelas="size-5 text-bahaya" />
                        {{ __('wky.dashboard.amaran_stok_rendah') }}
                    </span>
                    <a href="{{ route('products.index', ['stok_rendah' => 1]) }}" class="btn-garis btn-kecil">{{ __('wky.aksi.lihat_semua') }}</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="jadual">
                        <thead>
                            <tr>
                                <th>{{ __('wky.medan.produk') }}</th>
                                <th class="text-right">{{ __('wky.medan.stok') }}</th>
                                <th class="text-right">{{ __('wky.dashboard.minimum') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($stokRendah as $produk)
                            <tr>
                                <td>
                                    <a href="{{ route('products.show', $produk) }}" class="font-medium text-teks hover:text-aksen-terang">{{ $produk->nama }}</a>
                                    <p class="text-xs text-malap">{{ $produk->sku }}</p>
                                </td>
                                <td class="text-right"><span class="lencana-bahaya">{{ $produk->stok }}</span></td>
                                <td class="text-right text-malap">{{ $produk->stok_minimum }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-8 text-center text-malap">{{ __('wky.dashboard.tiada_stok_kritikal') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{--
                Kad amaran luput hanya muncul apabila ada lot yang perlu
                dilihat. Kad kosong yang kekal di dashboard setiap hari akan
                berhenti dibaca, dan amaran yang tidak dibaca tidak berguna.
            --}}
            @if ($batchLuput->isNotEmpty())
                <div class="kad">
                    <div class="kad-kepala">
                        <span class="flex items-center gap-2 font-semibold">
                            <x-ikon nama="kalendar" kelas="size-5 text-bahaya" />
                            {{ __('wky.batch.amaran_luput') }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="jadual">
                            <thead>
                                <tr>
                                    <th>{{ __('wky.medan.produk') }}</th>
                                    <th>{{ __('wky.batch.no_batch') }}</th>
                                    <th>{{ __('wky.batch.tarikh_luput') }}</th>
                                    <th class="text-right">{{ __('wky.medan.kuantiti') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($batchLuput as $batch)
                                <tr>
                                    <td>
                                        <a href="{{ route('products.show', $batch->product_id) }}" class="font-medium text-teks hover:text-aksen-terang">{{ $batch->product?->nama }}</a>
                                        <p class="text-xs text-malap">{{ $batch->product?->sku }}</p>
                                    </td>
                                    <td><code>{{ $batch->no_batch }}</code></td>
                                    <td>
                                        <span class="{{ $batch->kelasLuput() }} whitespace-nowrap">{{ $batch->labelLuput() }}</span>
                                        <p class="mt-0.5 text-xs text-malap">{{ $batch->tarikh_luput?->format('d/m/Y') }}</p>
                                    </td>
                                    <td class="text-right font-medium">{{ $batch->kuantiti }} {{ $batch->product?->unit }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="kad">
                <div class="kad-kepala">
                    <span class="flex items-center gap-2 font-semibold">
                        <x-ikon nama="papan-klip" kelas="size-5 text-aksen" />
                        {{ __('wky.kiraan.tajuk') }}
                        @if ($kiraanDraf > 0)
                            <span class="lencana-kuning">{{ __('wky.dashboard.sesi_terbuka', ['bil' => $kiraanDraf]) }}</span>
                        @endif
                    </span>
                    <div class="tanpa-cetak flex gap-2">
                        <a href="{{ route('stock-counts.create') }}" class="btn-utama btn-kecil"><x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.dashboard.sesi_baru') }}</a>
                        <a href="{{ route('stock-counts.index') }}" class="btn-garis btn-kecil">{{ __('wky.aksi.lihat_semua') }}</a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="jadual">
                        <thead>
                            <tr>
                                <th>{{ __('wky.medan.kod') }}</th>
                                <th>{{ __('wky.medan.status') }}</th>
                                <th class="text-right">{{ __('wky.dashboard.progres') }}</th>
                                <th>{{ __('wky.dashboard.dibuka_oleh') }}</th>
                                <th>{{ __('wky.medan.tarikh') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($sesiKiraan as $sesi)
                            <tr>
                                <td><a href="{{ route('stock-counts.show', $sesi) }}"><code>{{ $sesi->kod }}</code></a></td>
                                <td><span class="{{ $sesi->kelasStatus() }}">{{ $sesi->labelStatus() }}</span></td>
                                <td class="text-right">{{ $sesi->items_dikira_count }} / {{ $sesi->items_count }}</td>
                                <td class="text-sm">{{ $sesi->pembuka?->name ?? __('wky.umum.kosong') }}</td>
                                <td class="text-sm whitespace-nowrap text-malap">{{ $sesi->created_at->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-malap">{{ __('wky.dashboard.tiada_sesi_kiraan') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-4 xl:col-span-5">
            <div class="kad">
                <div class="kad-kepala !block">
                    <p class="flex items-center gap-2 font-semibold">
                        <x-ikon nama="carta-naik" kelas="size-5 text-aksen" />
                        {{ __('wky.dashboard.ringkasan_bulanan') }}
                    </p>
                    <p class="text-xs text-malap">{{ __('wky.dashboard.ringkasan_subtajuk') }}</p>
                </div>
                <div class="kad-badan">
                    <canvas id="cartaRingkasan" height="170"></canvas>
                </div>
            </div>

            <div class="kad">
                <div class="kad-kepala">
                    <span class="flex items-center gap-2 font-semibold">
                        <x-ikon nama="jam" kelas="size-5 text-aksen" />
                        {{ __('wky.dashboard.pergerakan_terkini') }}
                    </span>
                    <a href="{{ route('stock.index') }}" class="btn-garis btn-kecil">{{ __('wky.aksi.lihat_semua') }}</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="jadual">
                        <thead>
                            <tr>
                                <th>{{ __('wky.medan.produk') }}</th>
                                <th>{{ __('wky.medan.jenis') }}</th>
                                <th class="text-right">{{ __('wky.medan.kuantiti') }}</th>
                                <th>{{ __('wky.medan.tarikh') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($pergerakanTerkini as $gerak)
                            <tr>
                                <td>{{ $gerak->product?->nama ?? __('wky.umum.kosong') }}</td>
                                <td><span class="{{ $gerak->kelasJenis() }}">{{ $gerak->labelJenis() }}</span></td>
                                <td class="text-right">{{ $gerak->kuantiti }}</td>
                                <td class="text-sm whitespace-nowrap text-malap">{{ $gerak->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-malap">{{ __('wky.dashboard.tiada_pergerakan') }}</td></tr>
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
    <script>
        /*
            Dibungkus dalam DOMContentLoaded kerana Chart.js datang daripada
            app.js, yang dimuatkan sebagai <script type="module"> dan oleh itu
            ditangguhkan. Skrip klasik dalam <body> seperti ini berjalan semasa
            halaman masih dihurai — iaitu SEBELUM modul itu sempat menetapkan
            window.Chart.

            Tanpa pembungkus ini, baris `new Chart(...)` melontar
            "Chart is not defined" dan kad Ringkasan Bulanan kekal kosong pada
            setiap muatan.
        */
        document.addEventListener('DOMContentLoaded', function () {
            const kanvas = document.getElementById('cartaRingkasan');
            const data = @json($ringkasanBulanan);

            if (! kanvas || typeof Chart === 'undefined') {
                return;
            }

            /*
                Warna carta dibaca daripada token tema dan bukan ditulis tetap,
                supaya carta mengikut palet yang sama seperti seluruh halaman.

                Ia dibaca sekali semasa carta dibina. Menogol tema selepas itu
                tidak mengecat semula carta — Chart.js menyimpan warna ini dalam
                konfigurasinya sendiri — jadi carta hanya bertukar tona selepas
                halaman dimuat semula.
            */
            const gaya = getComputedStyle(document.documentElement);
            const token = (nama) => gaya.getPropertyValue(nama).trim();

            const warnaMasuk = `rgb(${token('--rgb-aksen')})`;
            const warnaKeluar = `rgb(${token('--rgb-aksen-terang')})`;
            const warnaLabel = token('--color-malap');
            const warnaGrid = token('--color-bingkai');

            const kecerunan = (ctx, warna) => {
                const g = ctx.createLinearGradient(0, 0, 0, 200);
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
                            borderColor: warnaMasuk,
                            backgroundColor: kecerunan(kanvas.getContext('2d'), warnaMasuk),
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                        },
                        {
                            label: @json(__('wky.dashboard.pengeluaran')),
                            data: data.keluar,
                            borderColor: warnaKeluar,
                            backgroundColor: kecerunan(kanvas.getContext('2d'), warnaKeluar),
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
                        legend: { labels: { color: warnaLabel, boxWidth: 12, font: { size: 11 } } },
                    },
                    scales: {
                        x: { ticks: { color: warnaLabel, font: { size: 10 } }, grid: { color: warnaGrid } },
                        y: { beginAtZero: true, ticks: { color: warnaLabel, font: { size: 10 } }, grid: { color: warnaGrid } },
                    },
                },
            });
        });
    </script>
@endpush
