@extends('layouts.app')
@section('tajuk', __('wky.kiraan.tajuk_papar', ['kod' => $sesi->kod]))

@section('kandungan')
    @php
        $dikira = $sesi->items->filter->sudahDikira();
        $berbeza = $dikira->filter(fn ($item) => $item->beza() !== 0);
        $lebih = $dikira->sum(fn ($item) => max($item->beza(), 0));
        $kurang = $dikira->sum(fn ($item) => min($item->beza(), 0));
    @endphp

    <div class="mb-4 grid gap-4 lg:grid-cols-3">
        <div class="kad kad-badan lg:col-span-2">
            <div class="mb-3 flex items-center gap-2">
                <span class="{{ $sesi->kelasStatus() }}">{{ $sesi->labelStatus() }}</span>
                <code>{{ $sesi->kod }}</code>
            </div>

            <dl class="grid gap-y-2 text-sm sm:grid-cols-[10rem_1fr]">
                <dt class="text-malap">{{ __('wky.medan.lokasi') }}</dt>
                <dd class="font-medium">{{ $sesi->location?->nama ?? __('wky.umum.kosong') }}</dd>

                <dt class="text-malap">{{ __('wky.kiraan.skop') }}</dt>
                <dd>{{ $sesi->category?->nama ?? __('wky.umum.semua_kategori') }}</dd>

                <dt class="text-malap">{{ __('wky.dashboard.dibuka_oleh') }}</dt>
                <dd>
                    {{ __('wky.kiraan.dibuka_oleh_pada', [
                        'nama' => $sesi->pembuka?->name ?? __('wky.umum.kosong'),
                        'tarikh' => $sesi->created_at->format('d/m/Y H:i'),
                    ]) }}
                </dd>

                @if ($sesi->disahkan_pada)
                    <dt class="text-malap">{{ __('wky.kiraan.disahkan_oleh') }}</dt>
                    <dd>
                        {{ __('wky.kiraan.dibuka_oleh_pada', [
                            'nama' => $sesi->pengesah?->name ?? __('wky.umum.kosong'),
                            'tarikh' => $sesi->disahkan_pada->format('d/m/Y H:i'),
                        ]) }}
                    </dd>
                @endif

                @if ($sesi->catatan)
                    <dt class="text-malap">{{ __('wky.medan.catatan') }}</dt>
                    <dd class="text-malap">{{ $sesi->catatan }}</dd>
                @endif
            </dl>
        </div>

        <div class="kad kad-badan space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-malap">{{ __('wky.kiraan.sudah_dikira') }}</span>
                <span class="font-semibold"><span id="bilDikira">{{ $dikira->count() }}</span> / {{ $sesi->items->count() }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-malap">{{ __('wky.kiraan.produk_berbeza') }}</span>
                <span class="font-semibold text-amber-400" id="bilBerbeza">{{ $berbeza->count() }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-malap">{{ __('wky.kiraan.jumlah_lebih') }}</span>
                <span class="font-semibold text-emerald-400" id="jumlahLebih">+{{ number_format($lebih) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-malap">{{ __('wky.kiraan.jumlah_kurang') }}</span>
                <span class="font-semibold text-bahaya-terang" id="jumlahKurang">{{ number_format($kurang) }}</span>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('stock-counts.update', $sesi) }}" id="borangKiraan" class="kad">
        @csrf
        @method('PUT')

        <div class="kad-kepala">
            <span class="flex items-center gap-2 font-semibold">
                <x-ikon nama="senarai" kelas="size-5 text-aksen" />
                {{ __('wky.kiraan.senarai_produk') }}
            </span>
            @if ($sesi->isDraf())
                <span class="text-xs text-malap">{{ __('wky.kiraan.biarkan_kosong') }}</span>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.sku') }}</th>
                        <th>{{ __('wky.medan.produk') }}</th>
                        <th>{{ __('wky.medan.kategori') }}</th>
                        <th class="text-right">{{ __('wky.kiraan.kuantiti_rekod') }}</th>
                        <th class="w-36 text-right">{{ __('wky.kiraan.kuantiti_fizikal') }}</th>
                        <th class="text-right">{{ __('wky.kiraan.beza') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($sesi->items as $item)
                    @php $beza = $item->beza(); @endphp
                    <tr>
                        <td><code>{{ $item->product?->sku ?? __('wky.umum.kosong') }}</code></td>
                        <td>{{ $item->product?->nama ?? __('wky.kiraan.produk_dipadam') }}</td>
                        <td class="text-malap">{{ $item->product?->category?->nama ?? __('wky.umum.kosong') }}</td>
                        <td class="text-right">{{ number_format($item->kuantiti_rekod) }}</td>
                        <td class="text-right">
                            @if ($sesi->isDraf())
                                <input type="number" min="0" inputmode="numeric"
                                       name="kuantiti[{{ $item->id }}]"
                                       value="{{ old("kuantiti.{$item->id}", $item->kuantiti_fizikal) }}"
                                       data-rekod-nilai="{{ $item->kuantiti_rekod }}"
                                       class="text-right"
                                       aria-label="{{ __('wky.kiraan.kuantiti_fizikal') }}">
                            @else
                                {{ $item->sudahDikira() ? number_format($item->kuantiti_fizikal) : __('wky.umum.kosong') }}
                            @endif
                        </td>
                        <td class="text-right font-medium" data-beza>
                            @if ($beza === null)
                                <span class="text-malap">{{ __('wky.umum.kosong') }}</span>
                            @else
                                <span class="{{ $beza < 0 ? 'text-bahaya-terang' : ($beza > 0 ? 'text-emerald-400' : 'text-malap') }}">
                                    {{ $beza > 0 ? '+' : '' }}{{ number_format($beza) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="kad-kaki">
            @if ($sesi->isDraf())
                <button type="submit" class="btn-garis"><x-ikon nama="simpan" kelas="size-4" /> {{ __('wky.kiraan.simpan_draf') }}</button>
            @endif
            <a href="{{ route('stock-counts.index') }}" class="btn-garis ml-auto">{{ __('wky.aksi.kembali') }}</a>
        </div>
    </form>

    @if ($sesi->isDraf())
        <div class="mt-4 flex flex-wrap gap-2">
            <form method="POST" action="{{ route('stock-counts.confirm', $sesi) }}"
                  onsubmit="return confirm('{{ __('wky.kiraan.sahkan_confirm', ['kod' => $sesi->kod]) }}')">
                @csrf
                <button type="submit" class="btn-utama"><x-ikon nama="tanda-semak" kelas="size-4" /> {{ __('wky.kiraan.sahkan_laraskan') }}</button>
            </form>

            <form method="POST" action="{{ route('stock-counts.destroy', $sesi) }}"
                  onsubmit="return confirm('{{ __('wky.kiraan.batal_confirm', ['kod' => $sesi->kod]) }}')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-bahaya"><x-ikon nama="silang-bulat" kelas="size-4" /> {{ __('wky.kiraan.batalkan_sesi') }}</button>
            </form>
        </div>

        <p class="mt-4 text-sm text-malap">
            {!! __('wky.kiraan.nota_pengesahan', [
                'pelarasan' => '<strong class="text-teks">' . e(__('wky.stok.pelarasan')) . '</strong>',
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
                            sel.innerHTML = '<span class="text-malap">' + kosong + '</span>';
                            return;
                        }

                        const beza = parseInt(medan.value, 10) - rekod;
                        dikira++;

                        if (beza !== 0) {
                            berbeza++;
                            beza > 0 ? lebih += beza : kurang += beza;
                        }

                        const warna = beza < 0 ? 'text-bahaya-terang' : (beza > 0 ? 'text-emerald-400' : 'text-malap');
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
