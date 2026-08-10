@props(['kelas' => 'size-40'])

{{--
    Logo WKY Inventory.

    Jika anda mempunyai fail logo sebenar, letakkan ia di public/images/ sebagai
    logo-wky.svg, .png, atau .webp — komponen ini akan menggunakannya secara
    automatik dan melangkau lukisan SVG di bawah. Tiada perubahan kod diperlukan.
--}}
@php
    $logo = collect(['svg', 'png', 'webp', 'jpg'])
        ->map(fn (string $sambungan) => "images/logo-wky.{$sambungan}")
        ->first(fn (string $laluan) => file_exists(public_path($laluan)));
@endphp

@if ($logo !== null)
    <img src="{{ asset($logo) }}" alt="{{ config('app.name') }}"
         {{ $attributes->merge(['class' => $kelas . ' object-contain']) }}>
@else
    <svg {{ $attributes->merge(['class' => $kelas]) }}
         viewBox="0 0 120 128" fill="none" xmlns="http://www.w3.org/2000/svg"
         role="img" aria-label="{{ config('app.name') }}">
        <defs>
            <linearGradient id="wky-bingkai" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#fca5a5" />
                <stop offset="55%" stop-color="#dc2626" />
                <stop offset="100%" stop-color="#7f1d1d" />
            </linearGradient>
            <linearGradient id="wky-teks" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#ffffff" />
                <stop offset="100%" stop-color="#fca5a5" />
            </linearGradient>
        </defs>

        {{-- Kiub isometrik: heksagon luar, heksagon dalam nipis, dan rusuk atas. --}}
        <polygon points="60,8 106,35 106,89 60,116 14,89 14,35"
                 stroke="url(#wky-bingkai)" stroke-width="3" stroke-linejoin="round" fill="none" />
        <polygon points="60,19 96,40 96,84 60,105 24,84 24,40"
                 stroke="url(#wky-bingkai)" stroke-width="1" stroke-linejoin="round"
                 fill="none" opacity="0.45" />

        <path d="M14 35 L60 62 L106 35 M60 62 L60 116"
              stroke="url(#wky-bingkai)" stroke-width="1" opacity="0.3" fill="none" />

        <text x="60" y="70" text-anchor="middle" fill="url(#wky-teks)"
              font-family="'Instrument Sans', ui-sans-serif, system-ui, sans-serif"
              font-size="30" font-weight="700" letter-spacing="1">WKY</text>
    </svg>
@endif
