<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kunci API Anthropic
    |--------------------------------------------------------------------------
    |
    | Daftar di https://console.anthropic.com untuk mendapatkan kunci, kemudian
    | letakkan dalam .env sebagai ANTHROPIC_API_KEY. Tanpa kunci ini, modul
    | Imbas Invois akan memaparkan mesej yang jelas dan tidak memanggil API.
    |
    */

    'api_key' => env('ANTHROPIC_API_KEY'),

    'model' => env('ANTHROPIC_MODEL', 'claude-opus-5'),

    /*
    | Tahap usaha untuk pengekstrakan invois. Membaca invois ialah kerja
    | penglihatan dan pengekstrakan, bukan penaakulan mendalam — 'medium'
    | memberi ketepatan yang baik tanpa kos dan kelewatan yang berlebihan.
    | Pilihan: low, medium, high, xhigh, max.
    */
    'effort' => env('ANTHROPIC_EFFORT', 'medium'),

    /*
    | Had masa panggilan API dalam saat. Imbasan invois berbilang halaman
    | boleh mengambil masa; nilai ini juga digunakan untuk menaikkan had masa
    | pelaksanaan PHP semasa permintaan imbasan.
    */
    'timeout' => (int) env('ANTHROPIC_TIMEOUT', 180),

    /*
    | Saiz maksimum fail invois yang boleh dimuat naik, dalam kilobait.
    | Had permintaan API ialah 32 MB termasuk pengekodan base64, jadi had
    | lalai 10 MB memberi ruang yang selamat.
    */
    'saiz_maks_kb' => (int) env('ANTHROPIC_SAIZ_MAKS_KB', 10240),

];
