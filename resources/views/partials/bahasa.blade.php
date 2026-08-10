{{-- Penukar bahasa. Pautan kembali ke halaman semasa selepas locale disimpan dalam sesi. --}}
<div class="tanpa-cetak inline-flex overflow-hidden rounded-md border border-bingkai" role="group" aria-label="{{ __('wky.nav.bahasa') }}">
    @foreach (config('bahasa.sokongan') as $kod => $bahasa)
        <a href="{{ route('locale.switch', $kod) }}"
           title="{{ $bahasa['nama'] }}"
           class="px-3 py-2 text-xs font-medium transition-colors
                  {{ app()->getLocale() === $kod
                      ? 'bg-merah text-white'
                      : 'bg-transparent text-malap hover:bg-tinggi hover:text-white' }}"
           @if (app()->getLocale() === $kod) aria-current="true" @endif>{{ $bahasa['label'] }}</a>
    @endforeach
</div>
