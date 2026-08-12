{{--
    Butang tindakan pantas yang terapung di penjuru bawah kanan setiap halaman.

    Ia menggunakan pencetus data-jatuh yang sama seperti menu lain, jadi ia
    mewarisi tutup-bila-klik-luar dan tutup-bila-Escape tanpa JavaScript baharu.

    Imbas Resit dan Muat Naik kedua-duanya menuju ke halaman imbas yang sama —
    ia memang satu halaman — tetapi membawa ?mod= supaya halaman itu membuka
    alat yang betul dan pengguna tidak perlu mencarinya sendiri.
--}}
@php
    $tindakan = [
        [
            'label' => __('wky.pantas.imbas_resit'),
            'ikon' => 'imbas',
            'url' => route('invoice-scans.create', ['mod' => 'kamera']),
        ],
        [
            'label' => __('wky.pantas.muat_naik'),
            'ikon' => 'simpan',
            'url' => route('invoice-scans.create', ['mod' => 'fail']),
        ],
        [
            'label' => __('wky.pantas.tambah_produk'),
            'ikon' => 'kotak',
            'url' => route('products.create'),
        ],
        [
            'label' => __('wky.pantas.tambah_kategori'),
            'ikon' => 'tag',
            'url' => route('categories.create'),
        ],
    ];
@endphp

<div class="tanpa-cetak fixed right-5 bottom-5 z-40 flex flex-col items-end gap-3">
    {{-- Kelas 'hidden' ditogol oleh data-jatuh; .menu-pantas:not(.hidden) yang
         memulihkan display:flex, kerana utiliti 'flex' dan 'hidden' bertelagah. --}}
    <div id="menu-pantas" class="menu-pantas hidden" role="menu"
         aria-label="{{ __('wky.pantas.tajuk') }}">
        @foreach ($tindakan as $t)
            <a href="{{ $t['url'] }}" class="pautan-pantas" role="menuitem">
                <span>{{ $t['label'] }}</span>
                <span class="ikon-pantas"><x-ikon :nama="$t['ikon']" kelas="size-5" /></span>
            </a>
        @endforeach
    </div>

    <button type="button" class="butang-pantas" data-jatuh="menu-pantas"
            aria-expanded="false" aria-haspopup="true"
            aria-label="{{ __('wky.pantas.buka') }}"
            title="{{ __('wky.pantas.tajuk') }}">
        <x-ikon nama="tambah" kelas="size-7" />
    </button>
</div>
