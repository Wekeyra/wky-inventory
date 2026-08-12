@props(['id', 'wajib' => false])

{{--
    Medan kata laluan dengan butang mata untuk menyemak apa yang ditaip.

    type="button" adalah penting: butang tanpa jenis di dalam borang dikira
    sebagai butang hantar, jadi menekan mata akan menghantar borang itu.

    Label butang menyimpan kedua-dua teksnya sebagai data-* supaya JavaScript
    boleh menukarnya tanpa mengetahui bahasa halaman.

    'wajib' ialah prop dan bukan atribut yang dihantar terus, kerana arahan
    Blade seperti @required(...) di dalam tag komponen menyebabkan penghurai
    tag gagal dan komponen itu terpapar sebagai teks mentah pada halaman.
--}}
<div class="medan-kata-laluan">
    <input type="password" id="{{ $id }}" @required($wajib) {{ $attributes }}>

    <button type="button" class="butang-mata"
            data-tunjuk-kata-laluan="{{ $id }}"
            data-label-tunjuk="{{ __('wky.aksi.tunjuk_kata_laluan') }}"
            data-label-sembunyi="{{ __('wky.aksi.sembunyi_kata_laluan') }}"
            aria-controls="{{ $id }}" aria-pressed="false"
            aria-label="{{ __('wky.aksi.tunjuk_kata_laluan') }}"
            title="{{ __('wky.aksi.tunjuk_kata_laluan') }}">
        <x-ikon nama="mata" kelas="size-5" data-ikon="tunjuk" />
        <x-ikon nama="mata-tutup" kelas="size-5 hidden" data-ikon="sembunyi" />
    </button>
</div>
