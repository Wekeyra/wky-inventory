{{--
    Latar hiasan halaman log masuk: rangkaian titik bercahaya dan siluet bandar.

    Nod dijana dengan benih tetap supaya susunannya sama pada setiap muatan —
    latar yang berubah-ubah setiap kali halaman dibuka akan mengganggu, dan
    benih tetap juga bermakna view yang di-cache kekal konsisten.
--}}
@php
    mt_srand(20260810);

    $nod = [];

    for ($i = 0; $i < 78; $i++) {
        $nod[] = [
            'x' => mt_rand(0, 1200),
            'y' => mt_rand(0, 760),
            'r' => mt_rand(9, 26) / 10,
        ];
    }

    // Sambung hanya nod yang berdekatan; jarak menentukan kelegapan garis
    // supaya rangkaian nampak dalam dan bukan seperti jaring rata.
    $garis = [];
    $hadJarak = 165;

    foreach ($nod as $i => $a) {
        foreach (array_slice($nod, $i + 1, null, true) as $b) {
            $jarak = sqrt(($a['x'] - $b['x']) ** 2 + ($a['y'] - $b['y']) ** 2);

            if ($jarak < $hadJarak) {
                $garis[] = [$a, $b, round(0.30 * (1 - $jarak / $hadJarak), 3)];
            }
        }
    }

    // Siluet bandar: [x, lebar, tinggi] pada garis dasar y = 200.
    $bangunan = [
        [0, 62, 70], [66, 40, 52], [110, 72, 96], [186, 44, 60], [234, 56, 112], [294, 36, 76],
        [432, 52, 82], [488, 58, 122], [550, 40, 66],
        [628, 56, 92], [688, 46, 70], [738, 66, 132], [808, 40, 86], [852, 56, 102],
        [912, 36, 62], [952, 70, 142], [1026, 46, 76], [1076, 62, 106], [1142, 58, 82],
    ];
@endphp

<svg class="konstelasi" viewBox="0 0 1200 800" preserveAspectRatio="xMidYMid slice"
     fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <g stroke="rgb(var(--rgb-aksen))">
        @foreach ($garis as [$a, $b, $legap])
            <line x1="{{ $a['x'] }}" y1="{{ $a['y'] }}" x2="{{ $b['x'] }}" y2="{{ $b['y'] }}"
                  stroke-width="0.6" opacity="{{ $legap }}" />
        @endforeach
    </g>

    <g fill="rgb(var(--rgb-aksen-terang))">
        @foreach ($nod as $titik)
            <circle cx="{{ $titik['x'] }}" cy="{{ $titik['y'] }}" r="{{ $titik['r'] }}"
                    opacity="{{ $titik['r'] > 2 ? 0.75 : 0.45 }}" />
        @endforeach
    </g>
</svg>

<svg class="siluet-bandar" viewBox="0 0 1200 200" preserveAspectRatio="xMidYMax slice"
     xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <defs>
        <linearGradient id="bandar" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="var(--siluet-atas)" />
            <stop offset="100%" stop-color="var(--siluet-bawah)" />
        </linearGradient>
    </defs>

    <g fill="url(#bandar)">
        @foreach ($bangunan as [$x, $lebar, $tinggi])
            <rect x="{{ $x }}" y="{{ 200 - $tinggi }}" width="{{ $lebar }}" height="{{ $tinggi }}" />
        @endforeach

        {{-- Menara berkembar dengan puncaknya. --}}
        <rect x="340" y="25" width="34" height="175" />
        <rect x="388" y="25" width="34" height="175" />
        <path d="M357 25 L353 6 L361 6 Z M405 25 L401 6 L409 6 Z" />
        <rect x="374" y="70" width="14" height="16" />

        {{-- Menara tinggi dengan gelendong dan antena. --}}
        <rect x="600" y="40" width="22" height="160" />
        <ellipse cx="611" cy="52" rx="20" ry="11" />
        <rect x="608" y="12" width="6" height="30" />
    </g>
</svg>
