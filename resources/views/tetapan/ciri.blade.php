@extends('layouts.app')
@section('tajuk', __('wky.ciri.tajuk'))

@section('kandungan')
    <form method="POST" action="{{ route('ciri.update') }}" class="kad max-w-3xl">
        @csrf
        @method('PUT')

        <div class="kad-badan space-y-4">
            <p class="text-sm text-malap">{{ __('wky.ciri.nota') }}</p>

            <div class="amaran-info">
                <x-ikon nama="amaran" kelas="size-5 shrink-0" />
                <span>{{ __('wky.ciri.nota_data') }}</span>
            </div>

            <div class="space-y-3">
                @foreach ($senarai as $ciri)
                    <label for="ciri-{{ $ciri }}"
                           class="flex cursor-pointer items-start gap-3 rounded-lg border border-bingkai p-3 transition-colors hover:border-aksen">
                        <input type="checkbox" id="ciri-{{ $ciri }}" name="ciri[]" value="{{ $ciri }}"
                               class="mt-0.5 !w-auto" @checked($ruangKerja->adaCiri($ciri))>
                        <span class="min-w-0">
                            <span class="block font-medium text-teks">{{ __('wky.ciri.' . $ciri) }}</span>
                            <span class="mt-0.5 block text-xs text-malap">{{ __('wky.ciri.' . $ciri . '_nota') }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="kad-kaki">
            <button type="submit" class="btn-utama">{{ __('wky.aksi.simpan') }}</button>
        </div>
    </form>

    {{--
        Teras disenaraikan supaya jelas apa yang TIDAK boleh dimatikan, dan
        bukan hanya apa yang boleh. Tanpa ini, senarai kosong di atas kelihatan
        seperti sistem yang tidak melakukan apa-apa.
    --}}
    <div class="kad mt-6 max-w-3xl">
        <div class="kad-kepala">
            <span class="font-semibold">{{ __('wky.ciri.teras') }}</span>
        </div>
        <div class="kad-badan">
            <p class="mb-3 text-sm text-malap">{{ __('wky.ciri.teras_nota') }}</p>
            <ul class="grid gap-2 text-sm sm:grid-cols-2">
                @foreach (['produk', 'stok_masuk', 'stok_keluar', 'baki', 'amaran', 'pelarasan', 'laporan', 'audit'] as $teras)
                    <li class="flex items-center gap-2">
                        <x-ikon nama="tanda-semak" kelas="size-4 shrink-0 text-aksen" />
                        {{ __('wky.ciri.teras_' . $teras) }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
