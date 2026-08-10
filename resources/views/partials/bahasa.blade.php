{{-- Penukar bahasa. Pautan kembali ke halaman semasa selepas locale disimpan dalam sesi. --}}
<div class="btn-group btn-group-sm tanpa-cetak" role="group" aria-label="{{ __('wky.nav.bahasa') }}">
    @foreach (config('bahasa.sokongan') as $kod => $bahasa)
        <a class="btn {{ app()->getLocale() === $kod ? 'btn-primary' : 'btn-outline-secondary' }}"
           href="{{ route('locale.switch', $kod) }}"
           title="{{ $bahasa['nama'] }}"
           @if (app()->getLocale() === $kod) aria-current="true" @endif>{{ $bahasa['label'] }}</a>
    @endforeach
</div>
