@if (session('status'))
    <div class="amaran-jaya mb-4" role="alert" data-amaran>
        <x-ikon nama="tanda-semak" kelas="size-5 shrink-0" />
        <span class="flex-1">{{ session('status') }}</span>
        <button type="button" class="cursor-pointer opacity-70 hover:opacity-100" data-amaran-tutup aria-label="{{ __('wky.aksi.batal') }}">
            <x-ikon nama="silang" kelas="size-4" />
        </button>
    </div>
@endif

@if (session('ralat'))
    <div class="amaran-gagal mb-4" role="alert" data-amaran>
        <x-ikon nama="amaran" kelas="size-5 shrink-0" />
        <span class="flex-1">{{ session('ralat') }}</span>
        <button type="button" class="cursor-pointer opacity-70 hover:opacity-100" data-amaran-tutup aria-label="{{ __('wky.aksi.batal') }}">
            <x-ikon nama="silang" kelas="size-4" />
        </button>
    </div>
@endif

@if ($errors->any())
    <div class="amaran-gagal mb-4" role="alert" data-amaran>
        <x-ikon nama="amaran" kelas="size-5 shrink-0" />
        <div class="flex-1">
            <strong>{{ __('wky.umum.sila_betulkan') }}</strong>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $ralat)
                    <li>{{ $ralat }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="cursor-pointer opacity-70 hover:opacity-100" data-amaran-tutup aria-label="{{ __('wky.aksi.batal') }}">
            <x-ikon nama="silang" kelas="size-4" />
        </button>
    </div>
@endif
