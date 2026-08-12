{{--
    Penukar bahasa. Pautan menuju ke URL halaman semasa dengan ?bahasa=xx,
    yang dibaca oleh middleware SetLocale. Halaman terus dipaparkan dalam
    bahasa baharu tanpa ubah hala tambahan.
--}}
<div class="tanpa-cetak inline-flex overflow-hidden rounded-md border border-bingkai" role="group" aria-label="{{ __('wky.nav.bahasa') }}">
    @foreach (config('bahasa.sokongan') as $kod => $bahasa)
        <a href="{{ request()->fullUrlWithQuery(['bahasa' => $kod]) }}"
           title="{{ $bahasa['nama'] }}"
           class="px-3 py-2 text-xs font-medium transition-colors
                  {{ app()->getLocale() === $kod
                      ? 'bg-merah text-white'
                      : 'bg-transparent text-malap hover:bg-tinggi hover:text-white' }}"
           @if (app()->getLocale() === $kod) aria-current="true" @endif>{{ $bahasa['label'] }}</a>
    @endforeach
</div>
