@extends('layouts.app')
@section('tajuk', __('wky.kategori.tajuk'))

@section('kandungan')
    <div class="kad">
        <div class="kad-kepala">
            <form method="GET" class="flex w-full flex-wrap items-center gap-2">
                <input type="search" name="cari" value="{{ request('cari') }}"
                       placeholder="{{ __('wky.kategori.cari_placeholder') }}" class="!w-auto min-w-56 flex-1">
                <button type="submit" class="btn-garis btn-ikon" title="{{ __('wky.aksi.cari') }}"><x-ikon nama="cari" /></button>
                <a href="{{ route('categories.create') }}" class="btn-utama ml-auto"><x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.aksi.tambah') }}</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.kod') }}</th>
                        <th>{{ __('wky.medan.nama') }}</th>
                        <th>{{ __('wky.medan.keterangan') }}</th>
                        <th class="text-right">{{ __('wky.medan.produk') }}</th>
                        <th class="text-right">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($categories as $kategori)
                    <tr>
                        <td><code>{{ $kategori->kod }}</code></td>
                        <td class="font-medium">{{ $kategori->nama }}</td>
                        <td class="text-malap">{{ Str::limit($kategori->keterangan, 60) ?: __('wky.umum.kosong') }}</td>
                        <td class="text-right"><span class="lencana-kelabu">{{ $kategori->products_count }}</span></td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('categories.edit', $kategori) }}" class="btn-garis btn-ikon" title="{{ __('wky.aksi.kemas_kini') }}">
                                    <x-ikon nama="pensel" kelas="size-4" />
                                </a>
                                <form method="POST" action="{{ route('categories.destroy', $kategori) }}"
                                      onsubmit="return confirm('{{ __('wky.kategori.sahkan_padam', ['nama' => $kategori->nama]) }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-bahaya btn-ikon" title="{{ __('wky.aksi.padam') }}">
                                        <x-ikon nama="tong-sampah" kelas="size-4" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-malap">{{ __('wky.kategori.tiada_dijumpai') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="kad-kaki penomboran block">{{ $categories->links() }}</div>
        @endif
    </div>
@endsection
