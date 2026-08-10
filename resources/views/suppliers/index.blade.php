@extends('layouts.app')
@section('tajuk', __('wky.pembekal.tajuk'))

@section('kandungan')
    <div class="kad">
        <div class="kad-kepala">
            <form method="GET" class="flex w-full flex-wrap items-center gap-2">
                <input type="search" name="cari" value="{{ $cari }}"
                       placeholder="{{ __('wky.pembekal.cari_placeholder') }}" class="!w-auto min-w-56 flex-1">
                <button type="submit" class="btn-garis btn-ikon" title="{{ __('wky.aksi.cari') }}"><x-ikon nama="cari" /></button>
                <a href="{{ route('suppliers.create') }}" class="btn-utama ml-auto"><x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.aksi.tambah') }}</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.kod') }}</th>
                        <th>{{ __('wky.medan.nama') }}</th>
                        <th>{{ __('wky.medan.pegawai_perhubungan') }}</th>
                        <th>{{ __('wky.medan.telefon') }}</th>
                        <th class="text-right">{{ __('wky.medan.produk') }}</th>
                        <th>{{ __('wky.medan.status') }}</th>
                        <th class="text-right">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($suppliers as $pembekal)
                    <tr>
                        <td><code>{{ $pembekal->kod }}</code></td>
                        <td><a href="{{ route('suppliers.show', $pembekal) }}" class="pautan-jadual">{{ $pembekal->nama }}</a></td>
                        <td>{{ $pembekal->pegawai_perhubungan ?? __('wky.umum.kosong') }}</td>
                        <td class="whitespace-nowrap">{{ $pembekal->telefon ?? __('wky.umum.kosong') }}</td>
                        <td class="text-right"><span class="lencana-kelabu">{{ $pembekal->products_count }}</span></td>
                        <td>
                            <span class="{{ $pembekal->aktif ? 'lencana-hijau' : 'lencana-kelabu' }}">
                                {{ $pembekal->aktif ? __('wky.umum.aktif') : __('wky.umum.tidak_aktif') }}
                            </span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('suppliers.edit', $pembekal) }}" class="btn-garis btn-ikon" title="{{ __('wky.aksi.kemas_kini') }}">
                                    <x-ikon nama="pensel" kelas="size-4" />
                                </a>
                                <form method="POST" action="{{ route('suppliers.destroy', $pembekal) }}"
                                      onsubmit="return confirm('{{ __('wky.pembekal.sahkan_padam', ['nama' => $pembekal->nama]) }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-bahaya btn-ikon" title="{{ __('wky.aksi.padam') }}">
                                        <x-ikon nama="tong-sampah" kelas="size-4" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-malap">{{ __('wky.pembekal.tiada_dijumpai') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($suppliers->hasPages())
            <div class="kad-kaki penomboran block">{{ $suppliers->links() }}</div>
        @endif
    </div>
@endsection
