@props(['sasaran', 'hantar' => false])

{{--
    Butang imbas barcode dengan kamera peranti.

    Butang bermula tersembunyi dan hanya didedahkan oleh JavaScript apabila
    pelayar menyokong BarcodeDetector. Pelayar yang tidak menyokongnya tidak
    kehilangan apa-apa: pengimbas barcode USB di kaunter menaip kodnya terus ke
    dalam medan seperti papan kekunci, jadi medan itu tetap berfungsi.
--}}

@php($modal = 'modal-imbas-' . $sasaran)

<button type="button" class="btn-garis btn-ikon hidden" data-imbas-barcode="{{ $sasaran }}"
        @if ($hantar) data-imbas-hantar="1" @endif
        data-modal-buka="{{ $modal }}" title="{{ __('wky.barcode.imbas') }}"
        aria-label="{{ __('wky.barcode.imbas') }}">
    <x-ikon nama="barcode" kelas="size-4" />
</button>

<div id="{{ $modal }}" data-modal
     data-ralat-ditolak="{{ __('wky.imbas.kamera_ditolak') }}"
     data-ralat-gagal="{{ __('wky.imbas.kamera_gagal') }}"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-sm [&:not(.hidden)]:flex"
     role="dialog" aria-modal="true" aria-labelledby="tajuk-{{ $modal }}">
    <div class="kad w-full max-w-lg shadow-2xl">
        <div class="kad-kepala">
            <h2 id="tajuk-{{ $modal }}" class="flex items-center gap-2 font-semibold">
                <x-ikon nama="barcode" kelas="size-5 text-aksen" />
                {{ __('wky.barcode.tajuk') }}
            </h2>
            <button type="button" class="cursor-pointer text-malap hover:text-teks" data-modal-tutup
                    aria-label="{{ __('wky.aksi.batal') }}">
                <x-ikon nama="silang" />
            </button>
        </div>

        <div class="kad-badan space-y-3">
            <div class="amaran-gagal hidden" data-imbas-ralat role="alert">
                <x-ikon nama="amaran" kelas="size-5 shrink-0" />
                <span data-imbas-ralat-teks></span>
            </div>

            <div class="rangka-kamera">
                <video data-imbas-video playsinline autoplay muted></video>
            </div>

            <p class="text-xs text-malap">{{ __('wky.barcode.petua') }}</p>
        </div>

        <div class="kad-kaki">
            <button type="button" class="btn-garis" data-modal-tutup>{{ __('wky.aksi.batal') }}</button>
        </div>
    </div>
</div>
