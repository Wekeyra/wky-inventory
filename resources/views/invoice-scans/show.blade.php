@extends('layouts.app')
@section('tajuk', __('wky.imbas.tajuk_papar', ['kod' => $imbasan->kod]))

@section('kandungan')
    @php
        $bolehDiproses = $imbasan->items->filter->bolehDiproses();
        $tiadaPadanan = $imbasan->items->filter(fn ($item) => ! $item->sudahPadan());
        $dilangkau = $imbasan->items->filter(fn ($item) => $item->dilangkau);
        $adaImej = str_starts_with($imbasan->jenis_mime, 'image/');
        $belumDibaca = $imbasan->isDraf() && $imbasan->belumDibaca();
    @endphp

    <div class="mb-4 grid gap-4 {{ $belumDibaca ? '' : 'lg:grid-cols-3' }}">
        <div class="kad kad-badan {{ $belumDibaca ? '' : 'lg:col-span-2' }}">
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <span class="{{ $imbasan->kelasStatus() }}">{{ $imbasan->labelStatus() }}</span>
                <code>{{ $imbasan->kod }}</code>
                <a href="{{ route('invoice-scans.file', $imbasan) }}" target="_blank" rel="noopener"
                   class="btn-garis btn-kecil ml-auto">
                    <x-ikon nama="mata" kelas="size-4" /> {{ __('wky.imbas.lihat_invois') }}
                </a>
            </div>

            <dl class="grid gap-y-2 text-sm sm:grid-cols-[10rem_1fr]">
                <dt class="text-malap">{{ __('wky.imbas.tarikh_invois') }}</dt>
                <dd>{{ $imbasan->tarikh_invois?->format('d/m/Y') ?? __('wky.umum.kosong') }}</dd>

                <dt class="text-malap">{{ __('wky.imbas.pembekal_invois') }}</dt>
                <dd>{{ $imbasan->nama_pembekal ?? __('wky.umum.kosong') }}</dd>

                <dt class="text-malap">{{ __('wky.dashboard.dibuka_oleh') }}</dt>
                <dd>
                    {{ __('wky.imbas.dibaca_oleh_pada', [
                        'nama' => $imbasan->pembuka?->name ?? __('wky.umum.kosong'),
                        'tarikh' => $imbasan->created_at->format('d/m/Y H:i'),
                    ]) }}
                </dd>

                @if ($imbasan->disahkan_pada)
                    <dt class="text-malap">{{ __('wky.kiraan.disahkan_oleh') }}</dt>
                    <dd>
                        {{ __('wky.imbas.dibaca_oleh_pada', [
                            'nama' => $imbasan->pengesah?->name ?? __('wky.umum.kosong'),
                            'tarikh' => $imbasan->disahkan_pada->format('d/m/Y H:i'),
                        ]) }}
                    </dd>
                @endif
            </dl>

            @if ($adaImej)
                <img src="{{ route('invoice-scans.file', $imbasan) }}" alt="{{ $imbasan->nama_fail_asal }}"
                     class="mt-4 max-h-72 w-full rounded-lg border border-bingkai object-contain">
            @endif
        </div>

        {{-- Angka ringkasan tiada makna sebelum baris diekstrak. --}}
        <div class="kad kad-badan space-y-2 text-sm {{ $belumDibaca ? 'hidden' : '' }}">
            <div class="flex justify-between">
                <span class="text-malap">{{ __('wky.imbas.baris_dibaca') }}</span>
                <span class="font-semibold">{{ $imbasan->items->count() }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-malap">{{ __('wky.imbas.akan_direkod') }}</span>
                <span class="font-semibold text-emerald-400">{{ $bolehDiproses->count() }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-malap">{{ __('wky.imbas.perlu_dipilih') }}</span>
                <span class="font-semibold text-merah-terang">{{ $tiadaPadanan->count() }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-malap">{{ __('wky.imbas.dilangkau') }}</span>
                <span class="font-semibold text-amber-400">{{ $dilangkau->count() }}</span>
            </div>
        </div>
    </div>

    @if ($belumDibaca)
        <div class="kad kad-badan">
            <div class="amaran-info mb-4">
                <x-ikon nama="jam" kelas="size-5 shrink-0" />
                <div>
                    <strong>{{ __('wky.imbas.belum_dibaca_tajuk') }}</strong>
                    <p class="mt-1">
                        {{ __('wky.imbas.belum_dibaca_nota', ['butang' => __('wky.imbas.baca_dengan_ai')]) }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('invoice-scans.read', $imbasan) }}" id="borangBaca">
                    @csrf
                    <button type="submit" class="btn-utama" id="butangBaca">
                        <x-ikon nama="imbas" kelas="size-4" /> {{ __('wky.imbas.baca_dengan_ai') }}
                    </button>
                </form>

                <form method="POST" action="{{ route('invoice-scans.destroy', $imbasan) }}"
                      onsubmit="return confirm('{{ __('wky.imbas.sahkan_padam', ['kod' => $imbasan->kod]) }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-bahaya">
                        <x-ikon nama="tong-sampah" kelas="size-4" /> {{ __('wky.imbas.padam_imbasan') }}
                    </button>
                </form>
            </div>
        </div>

        @push('skrip')
            <script>
                (function () {
                    const borang = document.getElementById('borangBaca');
                    const butang = document.getElementById('butangBaca');
                    const menunggu = @json(__('wky.imbas.sedang_baca'));

                    borang.addEventListener('submit', function () {
                        butang.disabled = true;
                        butang.textContent = menunggu;
                    });
                })();
            </script>
        @endpush
    @else

    <form method="POST" action="{{ route('invoice-scans.update', $imbasan) }}" class="kad">
        @csrf
        @method('PUT')

        <div class="kad-badan grid gap-4 sm:grid-cols-3">
            <div>
                <label for="no_invois" class="mb-1 block font-medium">{{ __('wky.imbas.no_invois') }}</label>
                <input id="no_invois" name="no_invois" value="{{ old('no_invois', $imbasan->no_invois) }}" @disabled(! $imbasan->isDraf())>
            </div>

            <div>
                <label for="supplier_id" class="mb-1 block font-medium">{{ __('wky.medan.pembekal') }}</label>
                <select id="supplier_id" name="supplier_id" @disabled(! $imbasan->isDraf())>
                    <option value="">— {{ __('wky.umum.tiada') }} —</option>
                    @foreach ($suppliers as $pembekal)
                        <option value="{{ $pembekal->id }}" @selected(old('supplier_id', $imbasan->supplier_id) == $pembekal->id)>{{ $pembekal->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="catatan" class="mb-1 block font-medium">{{ __('wky.medan.catatan') }}</label>
                <input id="catatan" name="catatan" value="{{ old('catatan', $imbasan->catatan) }}" @disabled(! $imbasan->isDraf())>
            </div>
        </div>

        <div class="overflow-x-auto border-t border-bingkai">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.imbas.baris_invois') }}</th>
                        <th class="text-right">{{ __('wky.imbas.harga_unit') }}</th>
                        <th class="min-w-64">{{ __('wky.imbas.produk_sistem') }}</th>
                        <th class="w-28 text-right">{{ __('wky.medan.kuantiti') }}</th>
                        <th>{{ __('wky.imbas.padanan') }}</th>
                        @if ($imbasan->isDraf())
                            <th class="text-center">{{ __('wky.imbas.langkau') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @foreach ($imbasan->items as $item)
                    <tr class="{{ $item->sudahPadan() ? '' : 'bg-merah/5' }}">
                        <td>
                            <p class="font-medium">{{ $item->nama_invois }}</p>
                            <p class="text-xs text-malap">
                                {{ $item->sku_invois ? $item->sku_invois . ' · ' : '' }}{{ $item->kuantiti }} {{ __('wky.medan.unit') }}
                            </p>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            {{ $item->harga_unit !== null ? 'RM ' . number_format($item->harga_unit, 2) : __('wky.umum.kosong') }}
                        </td>
                        <td>
                            @if ($imbasan->isDraf())
                                <select name="baris[{{ $item->id }}][product_id]" aria-label="{{ __('wky.imbas.produk_sistem') }}">
                                    <option value="">{{ __('wky.imbas.pilih_produk') }}</option>
                                    @foreach ($products as $produk)
                                        <option value="{{ $produk->id }}" @selected(old("baris.{$item->id}.product_id", $item->product_id) == $produk->id)>
                                            {{ $produk->nama }} ({{ $produk->sku }})
                                        </option>
                                    @endforeach
                                </select>

                                {{--
                                    Baris yang tiada padanan selalunya bermakna produk itu
                                    memang belum wujud, bukan pengguna terlepas pandang.
                                    Pautan ini membuka borang produk dengan kod, nama dan
                                    harga daripada invois sudah terisi, dan memautkannya
                                    semula ke baris ini selepas disimpan.
                                --}}
                                @unless ($item->sudahPadan())
                                    <a href="{{ route('products.create', ['baris_imbasan' => $item->id]) }}"
                                       class="mt-1.5 inline-flex items-center gap-1 text-xs text-malap hover:text-merah">
                                        <x-ikon nama="tambah" kelas="size-3.5" />
                                        {{ __('wky.imbas.cipta_produk') }}
                                    </a>
                                @endunless
                            @else
                                {{ $item->product?->nama ?? __('wky.umum.kosong') }}
                            @endif
                        </td>
                        <td class="text-right">
                            @if ($imbasan->isDraf())
                                <input type="number" min="1" class="text-right"
                                       name="baris[{{ $item->id }}][kuantiti]"
                                       value="{{ old("baris.{$item->id}.kuantiti", $item->kuantiti) }}"
                                       aria-label="{{ __('wky.medan.kuantiti') }}">
                            @else
                                {{ number_format($item->kuantiti) }}
                            @endif
                        </td>
                        <td><span class="{{ $item->kelasPadanan() }}">{{ $item->labelPadanan() }}</span></td>
                        @if ($imbasan->isDraf())
                            <td class="text-center">
                                <input type="hidden" name="baris[{{ $item->id }}][dilangkau]" value="0">
                                <input type="checkbox" value="1" class="!w-auto"
                                       name="baris[{{ $item->id }}][dilangkau]"
                                       @checked(old("baris.{$item->id}.dilangkau", $item->dilangkau))
                                       aria-label="{{ __('wky.imbas.langkau') }}">
                            </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="kad-kaki">
            @if ($imbasan->isDraf())
                <button type="submit" class="btn-garis">
                    <x-ikon nama="simpan" kelas="size-4" /> {{ __('wky.imbas.simpan_pembetulan') }}
                </button>
            @endif
            <a href="{{ route('invoice-scans.index') }}" class="btn-garis ml-auto">{{ __('wky.aksi.kembali') }}</a>
        </div>
    </form>

    @if ($imbasan->isDraf())
        <div class="mt-4 flex flex-wrap gap-2">
            {{--
                Tiada soalan pengesahan di sini dengan sengaja. Halaman ini
                sendiri sudah menjadi skrin semakan: setiap baris, kuantiti dan
                produknya terpampang di atas, dan butang ini bertulis apa yang
                akan berlaku. Satu kotak dialog yang bertanya perkara yang sama
                sekali lagi hanya menjadi kebiasaan yang ditekan tanpa dibaca.

                Butang Padam di sebelahnya tetap bertanya, kerana tindakan itu
                membuang kerja yang sudah ada dan tiada skrin yang menunjukkan
                apa yang bakal hilang.
            --}}
            <form method="POST" action="{{ route('invoice-scans.confirm', $imbasan) }}">
                @csrf
                <button type="submit" class="btn-utama">
                    <x-ikon nama="tanda-semak" kelas="size-4" /> {{ __('wky.imbas.sahkan_rekod') }}
                </button>
            </form>

            <form method="POST" action="{{ route('invoice-scans.destroy', $imbasan) }}"
                  onsubmit="return confirm('{{ __('wky.imbas.sahkan_padam', ['kod' => $imbasan->kod]) }}')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-bahaya">
                    <x-ikon nama="tong-sampah" kelas="size-4" /> {{ __('wky.imbas.padam_imbasan') }}
                </button>
            </form>
        </div>

        <p class="mt-4 text-sm text-malap">
            {!! __('wky.imbas.nota_pengesahan', [
                'masuk' => '<strong class="text-teks">' . e(__('wky.stok.masuk')) . '</strong>',
                'rujukan' => '<code>' . e($imbasan->rujukanStok()) . '</code>',
            ]) !!}
        </p>
    @endif

    @endif
@endsection
