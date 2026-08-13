@props(['kelas' => 'size-7'])

{{--
    Tanda jenama "W" sahaja, untuk tempat sempit seperti kepala bar sisi.

    Ia fail berasingan daripada logo penuh kerana logo penuh membawa anak panah
    hitam di sebelah kanannya — anak panah itu hampir tidak kelihatan di atas
    bar sisi yang gelap, dan pada saiz 28px ia hanya menjadikan tanda itu
    bersepah tanpa menambah apa-apa yang boleh dikenali.

    Letakkan logo-wky-w.png (atau .svg/.webp) di public/images/ untuk
    menggantikannya. Tanpa fail itu, ikon kotak terbina digunakan semula supaya
    bar sisi tidak pernah kosong.
--}}
@php
    $tanda = collect(['svg', 'png', 'webp'])
        ->map(fn (string $sambungan) => "images/logo-wky-w.{$sambungan}")
        ->first(fn (string $laluan) => file_exists(public_path($laluan)));
@endphp

@if ($tanda !== null)
    <img src="{{ asset($tanda) }}" alt=""
         {{ $attributes->merge(['class' => $kelas . ' shrink-0 object-contain']) }}>
@else
    <x-ikon nama="kotak-jenama" :kelas="$kelas . ' shrink-0 text-aksen'" />
@endif
