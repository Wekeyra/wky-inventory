@props(['kelas' => 'text-3xl sm:text-4xl'])

{{--
    Kata jenama "WKY INVENTORY" mengikut susunan logo: WKY merah, INVENTORY putih.

    Ditulis sebagai teks dan bukan sebahagian daripada fail logo supaya ia kekal
    tajam pada setiap saiz skrin dan boleh dibaca pembaca skrin.

    Ia juga sengaja tidak menggunakan config('app.name'). Nama jenama tidak
    sepatutnya berubah mengikut tetapan hos — Laravel Cloud menetapkan APP_NAME
    kepada slug aplikasi ("wky-inventory"), yang memaparkan nama huruf kecil
    bertanda sempang pada halaman log masuk produksi.
--}}
<span {{ $attributes->merge(['class' => $kelas . ' font-bold tracking-[0.08em] whitespace-nowrap']) }}>
    <span class="text-merah-terang">WKY</span><span class="text-white"> INVENTORY</span>
</span>
