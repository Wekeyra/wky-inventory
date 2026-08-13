@extends('layouts.app')
@section('tajuk', $supplier->nama)

@section('kandungan')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="kad flex flex-col">
            <div class="kad-badan flex-1">
                <dl class="grid grid-cols-2 gap-y-3 text-sm">
                    <dt class="text-malap">{{ __('wky.medan.kod') }}</dt>
                    <dd><code>{{ $supplier->kod }}</code></dd>

                    <dt class="text-malap">{{ __('wky.medan.pegawai_perhubungan') }}</dt>
                    <dd>{{ $supplier->pegawai_perhubungan ?? __('wky.umum.kosong') }}</dd>

                    <dt class="text-malap">{{ __('wky.medan.telefon') }}</dt>
                    <dd>{{ $supplier->telefon ?? __('wky.umum.kosong') }}</dd>

                    <dt class="text-malap">{{ __('wky.medan.emel') }}</dt>
                    <dd class="break-all">{{ $supplier->emel ?? __('wky.umum.kosong') }}</dd>

                    <dt class="text-malap">{{ __('wky.medan.status') }}</dt>
                    <dd>
                        <span class="{{ $supplier->aktif ? 'lencana-hijau' : 'lencana-kelabu' }}">
                            {{ $supplier->aktif ? __('wky.umum.aktif') : __('wky.umum.tidak_aktif') }}
                        </span>
                    </dd>
                </dl>

                @if ($supplier->alamat)
                    <p class="mt-4 border-t border-bingkai pt-4 text-sm text-malap">{{ $supplier->alamat }}</p>
                @endif
            </div>

            <div class="kad-kaki">
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-utama btn-kecil"><x-ikon nama="pensel" kelas="size-4" /> {{ __('wky.aksi.kemas_kini') }}</a>
                <a href="{{ route('suppliers.index') }}" class="btn-garis btn-kecil ml-auto">{{ __('wky.aksi.kembali') }}</a>
            </div>
        </div>

        <div class="kad lg:col-span-2">
            <div class="kad-kepala">
                <span class="flex items-center gap-2 font-semibold">
                    <x-ikon nama="kotak" kelas="size-5 text-aksen" />
                    {{ __('wky.pembekal.produk_daripada') }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="jadual">
                    <thead>
                        <tr>
                            <th>{{ __('wky.medan.sku') }}</th>
                            <th>{{ __('wky.medan.nama') }}</th>
                            <th class="text-right">{{ __('wky.medan.harga_kos') }}</th>
                            <th class="text-right">{{ __('wky.medan.stok') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($supplier->products as $produk)
                        <tr>
                            <td><code>{{ $produk->sku }}</code></td>
                            <td><a href="{{ route('products.show', $produk) }}" class="pautan-jadual">{{ $produk->nama }}</a></td>
                            <td class="text-right whitespace-nowrap">RM {{ number_format($produk->harga_kos, 2) }}</td>
                            <td class="text-right whitespace-nowrap">{{ $produk->stok }} {{ $produk->unit }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-10 text-center text-malap">{{ __('wky.pembekal.tiada_produk') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
