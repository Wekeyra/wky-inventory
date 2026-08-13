<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} &middot; {{ __('wky.app.subtajuk') }}</title>
    <meta name="description" content="{{ __('wky.landing.hero_teks') }}">
    @include('partials.skrip-tema')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- overflow-x-hidden sahaja supaya halaman panjang ini kekal boleh diskrol. --}}
<body class="latar-log-masuk relative min-h-screen overflow-x-hidden">
    <x-latar-log-masuk />

    @php
        // Satu senarai, dipakai oleh nav desktop dan nav mudah alih supaya kedua-duanya
        // tidak boleh terpesong antara satu sama lain apabila pautan ditambah.
        $pautan = [
            'utama' => __('wky.landing.nav_utama'),
            'ciri' => __('wky.landing.nav_ciri'),
            'harga' => __('wky.landing.nav_harga'),
            'inventori' => __('wky.landing.nav_inventori'),
            'tentang' => __('wky.landing.nav_tentang'),
        ];
    @endphp

    <header class="nav-landing">
        <div class="mx-auto flex w-full max-w-6xl items-center gap-4 px-4 py-3">
            <a href="#utama" class="flex shrink-0 items-center gap-2.5">
                <x-logo-wky kelas="size-9" />
                <x-jenama-wky kelas="text-base sm:text-lg" />
            </a>

            <nav class="mx-auto hidden items-center gap-1 lg:flex" aria-label="{{ __('wky.landing.nav_menu') }}">
                @foreach ($pautan as $id => $label)
                    <a href="#{{ $id }}" class="pautan-nav">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-2 lg:ml-0">
                @include('partials.bahasa')
                <x-togol-tema />

                <a href="{{ route('login') }}" class="btn-garis hidden sm:inline-flex">
                    {{ __('wky.aksi.log_masuk') }}
                </a>
                <a href="{{ route('register') }}" class="btn-utama hidden sm:inline-flex">
                    {{ __('wky.auth.daftar') }}
                </a>

                <button type="button" class="btn-garis btn-ikon lg:hidden" data-jatuh="menu-landing"
                        aria-expanded="false" aria-label="{{ __('wky.landing.nav_menu') }}">
                    <x-ikon nama="bar" />
                </button>
            </div>
        </div>

        {{-- Menu mudah alih; guna pencetus data-jatuh yang sama seperti menu lain. --}}
        <div id="menu-landing" class="hidden border-t border-aksen/15 bg-latar/95 backdrop-blur-md lg:hidden">
            <nav class="mx-auto flex w-full max-w-6xl flex-col gap-1 px-4 py-3" aria-label="{{ __('wky.landing.nav_menu') }}">
                @foreach ($pautan as $id => $label)
                    <a href="#{{ $id }}" class="pautan-nav">{{ $label }}</a>
                @endforeach

                <div class="mt-2 flex gap-2 sm:hidden">
                    <a href="{{ route('login') }}" class="btn-garis flex-1">{{ __('wky.aksi.log_masuk') }}</a>
                    <a href="{{ route('register') }}" class="btn-utama flex-1">{{ __('wky.auth.daftar') }}</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="relative z-10">

        {{-- ---------- Hero ---------- --}}
        <section id="utama" class="seksyen-landing scroll-mt-24 pt-14 text-center sm:pt-20">
            <div class="mx-auto max-w-3xl px-4">
                <x-logo-wky kelas="jenama-log-masuk mx-auto size-28 sm:size-36" />

                <p class="mt-5 text-[0.7rem] tracking-[0.3em] text-malap uppercase sm:text-xs">
                    {{ __('wky.app.subtajuk') }}
                </p>

                <h1 class="mt-3 text-4xl font-bold tracking-tight text-teks sm:text-5xl">
                    {{ __('wky.landing.hero_tajuk') }}
                </h1>

                <p class="mx-auto mt-5 max-w-2xl text-base leading-relaxed text-malap sm:text-lg">
                    {{ __('wky.landing.hero_teks') }}
                </p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('register') }}" class="btn-utama px-6 py-2.5 text-base">
                        {{ __('wky.landing.hero_mula') }}
                    </a>
                    <a href="{{ route('login') }}" class="btn-garis px-6 py-2.5 text-base">
                        {{ __('wky.landing.hero_log_masuk') }}
                    </a>
                </div>

                {{-- Rantai aliran: imbas → stok → laporan. --}}
                <div class="mt-10 inline-flex flex-wrap items-center justify-center gap-x-3 gap-y-2
                            rounded-full border border-aksen/20 bg-permukaan/50 px-5 py-2.5 backdrop-blur-sm">
                    <span class="inline-flex items-center gap-1.5 text-sm text-malap">
                        <x-ikon nama="imbas" kelas="size-4 text-aksen-terang" />
                        {{ __('wky.landing.hero_rantai_imbas') }}
                    </span>
                    <x-ikon nama="anak-panah-kanan" kelas="size-3.5 text-malap/50" />
                    <span class="inline-flex items-center gap-1.5 text-sm text-malap">
                        <x-ikon nama="kotak" kelas="size-4 text-aksen-terang" />
                        {{ __('wky.landing.hero_rantai_stok') }}
                    </span>
                    <x-ikon nama="anak-panah-kanan" kelas="size-3.5 text-malap/50" />
                    <span class="inline-flex items-center gap-1.5 text-sm text-malap">
                        <x-ikon nama="dokumen-carta" kelas="size-4 text-aksen-terang" />
                        {{ __('wky.landing.hero_rantai_laporan') }}
                    </span>
                </div>
            </div>
        </section>

        {{-- ---------- Ciri ---------- --}}
        <section id="ciri" class="seksyen-landing scroll-mt-24">
            <div class="mx-auto max-w-6xl px-4">
                <x-tajuk-seksyen :tajuk="__('wky.landing.ciri_tajuk')" :teks="__('wky.landing.ciri_subtajuk')" />

                {{-- Salinan ciri dikongsi dengan halaman pendaftaran supaya kedua-duanya
                     tidak menyimpang apabila teksnya disunting. --}}
                @php
                    $ciri = [
                        ['kotak', 'produk'],
                        ['imbas', 'imbas'],
                        ['papan-klip', 'kiraan'],
                        ['anak-panah-dua-arah', 'pergerakan'],
                        ['dokumen-carta', 'laporan'],
                        ['pengguna-ramai', 'pasukan'],
                    ];
                @endphp

                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($ciri as [$ikon, $kunci])
                        <div class="kad-ciri">
                            <div class="ikon-ciri"><x-ikon :nama="$ikon" /></div>
                            <h3 class="mb-1.5 font-semibold text-teks">
                                {{ __("wky.auth.ciri_{$kunci}_tajuk") }}
                            </h3>
                            <p class="text-sm leading-relaxed text-malap">
                                {{ __("wky.auth.ciri_{$kunci}_teks") }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ---------- Harga ---------- --}}
        <section id="harga" class="seksyen-landing scroll-mt-24">
            <div class="mx-auto max-w-6xl px-4">
                <x-tajuk-seksyen :tajuk="__('wky.landing.harga_tajuk')" :teks="__('wky.landing.harga_subtajuk')" />

                @php
                    $pakej = [
                        ['kunci' => 'percuma', 'sebulan' => false, 'utama' => false, 'aksi' => 'harga_pilih'],
                        ['kunci' => 'perniagaan', 'sebulan' => true, 'utama' => true, 'aksi' => 'harga_pilih'],
                        ['kunci' => 'enterprise', 'sebulan' => false, 'utama' => false, 'aksi' => 'harga_hubungi'],
                    ];
                @endphp

                <div class="mt-10 grid items-start gap-5 lg:grid-cols-3">
                    @foreach ($pakej as $p)
                        <div class="{{ $p['utama'] ? 'kad-harga kad-harga-utama' : 'kad-harga' }}">
                            @if ($p['utama'])
                                <span class="lencana-aksen absolute -top-3 left-1/2 -translate-x-1/2">
                                    {{ __('wky.landing.harga_popular') }}
                                </span>
                            @endif

                            <h3 class="text-sm font-semibold tracking-wider text-aksen-terang uppercase">
                                {{ __("wky.landing.harga_{$p['kunci']}_nama") }}
                            </h3>

                            <p class="mt-3 flex items-baseline gap-1">
                                <span class="text-3xl font-bold text-teks">
                                    {{ __("wky.landing.harga_{$p['kunci']}_harga") }}
                                </span>
                                @if ($p['sebulan'])
                                    <span class="text-sm text-malap">{{ __('wky.landing.harga_sebulan') }}</span>
                                @endif
                            </p>

                            <p class="mt-2 text-sm text-malap">
                                {{ __("wky.landing.harga_{$p['kunci']}_teks") }}
                            </p>

                            <ul class="mt-5 space-y-2.5 border-t border-bingkai pt-5">
                                @for ($i = 1; $i <= 4; $i++)
                                    <li class="flex items-start gap-2 text-sm text-teks">
                                        <x-ikon nama="tanda-semak" kelas="mt-0.5 size-4 shrink-0 text-aksen-terang" />
                                        {{ __("wky.landing.harga_{$p['kunci']}_ciri_{$i}") }}
                                    </li>
                                @endfor
                            </ul>

                            <a href="{{ route('register') }}"
                               class="{{ $p['utama'] ? 'btn-utama' : 'btn-garis' }} mt-6 w-full">
                                {{ __("wky.landing.{$p['aksi']}") }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ---------- Inventori ---------- --}}
        <section id="inventori" class="seksyen-landing scroll-mt-24">
            <div class="mx-auto max-w-6xl px-4">
                <x-tajuk-seksyen :tajuk="__('wky.landing.inventori_tajuk')" :teks="__('wky.landing.inventori_subtajuk')" />

                <div class="mt-10 grid gap-4 lg:grid-cols-3">
                    @foreach ([1, 2, 3] as $langkah)
                        <div class="kad-ciri relative pt-6">
                            <span class="absolute top-4 right-5 text-4xl font-bold text-aksen/20 tabular-nums">
                                {{ $langkah }}
                            </span>
                            <h3 class="mb-1.5 font-semibold text-teks">
                                {{ __("wky.landing.inventori_langkah_{$langkah}_tajuk") }}
                            </h3>
                            <p class="text-sm leading-relaxed text-malap">
                                {{ __("wky.landing.inventori_langkah_{$langkah}_teks") }}
                            </p>
                        </div>
                    @endforeach
                </div>

                <p class="mt-10 text-center text-xs tracking-wider text-malap uppercase">
                    {{ __('wky.landing.inventori_modul') }}
                </p>

                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    @foreach (['produk', 'kategori', 'pembekal', 'imbas_invois', 'kiraan_stok', 'pergerakan_stok', 'laporan_bulanan'] as $modul)
                        <span class="rounded-full border border-aksen/20 bg-permukaan/50 px-3.5 py-1.5 text-sm text-teks">
                            {{ __("wky.nav.{$modul}") }}
                        </span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ---------- Tentang Kami ---------- --}}
        <section id="tentang" class="seksyen-landing scroll-mt-24">
            <div class="mx-auto max-w-6xl px-4">
                <x-tajuk-seksyen :tajuk="__('wky.landing.tentang_tajuk')" />

                <div class="mt-10 grid gap-8 lg:grid-cols-2 lg:gap-12">
                    <div class="space-y-4 text-base leading-relaxed text-malap">
                        <p>{{ __('wky.landing.tentang_teks_1') }}</p>
                        <p>{{ __('wky.landing.tentang_teks_2') }}</p>
                        <p>{{ __('wky.landing.tentang_teks_3') }}</p>
                    </div>

                    <div class="space-y-3">
                        @foreach ([['tanda-semak', 1], ['kotak', 2], ['pengguna-ramai', 3]] as [$ikon, $n])
                            <div class="flex items-start gap-3.5 rounded-xl border border-aksen/15 bg-permukaan/40 p-4">
                                <div class="ikon-ciri mb-0 size-10 shrink-0">
                                    <x-ikon :nama="$ikon" kelas="size-4" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-teks">
                                        {{ __("wky.landing.tentang_nilai_{$n}_tajuk") }}
                                    </h3>
                                    <p class="mt-0.5 text-sm text-malap">
                                        {{ __("wky.landing.tentang_nilai_{$n}_teks") }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ---------- Ajakan terakhir ---------- --}}
        <section class="seksyen-landing">
            <div class="mx-auto max-w-3xl px-4">
                <div class="kad-log-masuk text-center">
                    <h2 class="text-2xl font-bold text-teks sm:text-3xl">
                        {{ __('wky.landing.cta_tajuk') }}
                    </h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-malap sm:text-base">
                        {{ __('wky.landing.cta_teks') }}
                    </p>
                    <a href="{{ route('register') }}" class="btn-utama mt-6 px-6 py-2.5 text-base">
                        {{ __('wky.landing.hero_mula') }}
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="relative z-10 border-t border-aksen/15 py-8">
        <div class="mx-auto flex w-full max-w-6xl flex-col items-center gap-3 px-4 sm:flex-row sm:justify-between">
            <div class="flex items-center gap-2.5">
                <x-logo-wky kelas="size-7" />
                <x-jenama-wky kelas="text-sm" />
            </div>

            <p class="text-xs text-malap/70">
                &copy; {{ now()->format('Y') }} {{ config('app.name') }}. {{ __('wky.landing.footer_hak') }}
            </p>
        </div>
    </footer>
</body>
</html>
