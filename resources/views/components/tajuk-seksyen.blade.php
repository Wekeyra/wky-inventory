@props(['tajuk', 'teks' => null])

{{-- Tajuk seksyen halaman pendaratan: garis aksen, tajuk, dan teks pengenalan pilihan. --}}
<div class="text-center">
    <span class="mx-auto block h-px w-14 bg-gradient-to-r from-transparent via-aksen to-transparent"></span>

    <h2 class="mt-5 text-3xl font-bold tracking-tight text-teks sm:text-4xl">{{ $tajuk }}</h2>

    @if ($teks)
        <p class="mx-auto mt-3 max-w-2xl text-sm leading-relaxed text-malap sm:text-base">{{ $teks }}</p>
    @endif
</div>
