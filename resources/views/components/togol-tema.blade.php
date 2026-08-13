@props(['kelas' => ''])

{{--
    Togol terang/gelap. Ikon suria dan bulan kedua-duanya sentiasa dalam DOM;
    app.css yang menentukan mana kelihatan berdasarkan kelas "dark" pada
    <html>, supaya penukaran tidak melompat menunggu JavaScript melukis
    semula apa-apa. JavaScript (app.js) hanya menogol kelas itu sendiri dan
    menyimpan pilihan.
--}}
<button type="button" class="togol-tema {{ $kelas }}" data-togol-tema
        aria-pressed="false" aria-label="{{ __('wky.tema.tukar') }}" title="{{ __('wky.tema.tukar') }}">
    <x-ikon nama="suria" data-ikon="suria" kelas="size-4" />
    <x-ikon nama="bulan" data-ikon="bulan" kelas="size-4" />
</button>
