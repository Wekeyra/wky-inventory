@extends('layouts.app')
@section('tajuk', __('wky.imbas.tajuk_muat_naik'))

@section('kandungan')
    @unless ($adaKunci)
        <div class="amaran-gagal mb-4 max-w-2xl">
            <x-ikon nama="amaran" kelas="size-5 shrink-0" />
            <span>{{ __('wky.imbas.ralat_tiada_kunci') }}</span>
        </div>
    @endunless

    <form method="POST" action="{{ route('invoice-scans.store') }}" enctype="multipart/form-data"
          class="kad max-w-2xl" id="borangImbas">
        @csrf

        <div class="kad-badan space-y-4">
            <div>
                <label for="invois" class="mb-1 block font-medium">
                    {{ __('wky.imbas.fail_invois') }} <span class="text-merah">*</span>
                </label>
                <input type="file" id="invois" name="invois" required
                       accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
                       class="file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-merah-gelap file:px-3 file:py-1.5 file:text-sm file:text-white hover:file:bg-merah"
                       @error('invois') class="medan-ralat" @enderror>
                <p class="mt-1 text-xs text-malap">{{ __('wky.imbas.fail_nota', ['saiz' => round($saizMaksKb / 1024)]) }}</p>
                @error('invois') <p class="maklum-balas-ralat">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="catatan" class="mb-1 block font-medium">{{ __('wky.medan.catatan') }}</label>
                <textarea id="catatan" name="catatan" rows="2">{{ old('catatan') }}</textarea>
            </div>

            <div class="amaran-info">
                <span>
                    {!! __('wky.imbas.nota_muat_naik', [
                        'tidak' => '<strong>' . e(__('wky.umum.tiada')) . '</strong>',
                        'sahkan' => '<strong>' . e(__('wky.imbas.sahkan_rekod')) . '</strong>',
                    ]) !!}
                </span>
            </div>
        </div>

        <div class="kad-kaki">
            <button type="submit" class="btn-utama" id="butangImbas" @disabled(! $adaKunci)>
                <x-ikon nama="imbas" kelas="size-4" /> {{ __('wky.imbas.butang') }}
            </button>
            <a href="{{ route('invoice-scans.index') }}" class="btn-garis">{{ __('wky.aksi.batal') }}</a>
        </div>
    </form>
@endsection

@push('skrip')
    <script>
        // Membaca invois mengambil masa beberapa saat; butang dikunci supaya
        // pengguna tidak menghantar dokumen yang sama dua kali.
        (function () {
            const borang = document.getElementById('borangImbas');
            const butang = document.getElementById('butangImbas');
            const menunggu = @json(__('wky.imbas.sedang_baca'));

            borang.addEventListener('submit', function () {
                butang.disabled = true;
                butang.textContent = menunggu;
            });
        })();
    </script>
@endpush
