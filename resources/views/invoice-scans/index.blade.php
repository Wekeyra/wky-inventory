@extends('layouts.app')
@section('tajuk', __('wky.imbas.tajuk'))

@section('kandungan')
    <div class="kad">
        <div class="kad-kepala">
            <span class="flex items-center gap-2 font-semibold">
                <x-ikon nama="imbas" kelas="size-5 text-merah" />
                {{ __('wky.imbas.tajuk_senarai') }}
            </span>
            <a href="{{ route('invoice-scans.create') }}" class="btn-utama btn-kecil">
                <x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.imbas.imbas_baharu') }}
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.kod') }}</th>
                        <th>{{ __('wky.medan.status') }}</th>
                        <th>{{ __('wky.imbas.no_invois') }}</th>
                        <th>{{ __('wky.medan.pembekal') }}</th>
                        <th class="text-right">{{ __('wky.imbas.baris_dibaca') }}</th>
                        <th>{{ __('wky.dashboard.dibuka_oleh') }}</th>
                        <th>{{ __('wky.medan.tarikh') }}</th>
                        <th class="text-right">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($imbasan as $item)
                    <tr>
                        <td><code>{{ $item->kod }}</code></td>
                        <td><span class="{{ $item->kelasStatus() }}">{{ $item->labelStatus() }}</span></td>
                        <td>{{ $item->no_invois ?? __('wky.umum.kosong') }}</td>
                        <td>{{ $item->nama_pembekal ?? __('wky.umum.kosong') }}</td>
                        <td class="text-right">{{ $item->items_count }}</td>
                        <td>{{ $item->pembuka?->name ?? __('wky.umum.kosong') }}</td>
                        <td class="whitespace-nowrap text-malap">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('invoice-scans.show', $item) }}" class="btn-garis btn-kecil"
                                   title="{{ $item->isDraf() ? __('wky.aksi.kemas_kini') : __('wky.aksi.lihat') }}">
                                    <x-ikon :nama="$item->isDraf() ? 'pensel' : 'mata'" kelas="size-4" />
                                    {{ $item->isDraf() ? __('wky.aksi.teruskan') : __('wky.aksi.lihat') }}
                                </a>

                                {{--
                                    Butang padam hanya untuk draf. Imbasan yang telah
                                    disahkan menjana pergerakan stok yang merujuk kodnya,
                                    jadi memadamnya akan meninggalkan pergerakan yang
                                    menunjuk kepada imbasan yang tiada. Controller turut
                                    menahannya, jadi ini bukan satu-satunya sekatan.
                                --}}
                                @if ($item->isDraf())
                                    <form method="POST" action="{{ route('invoice-scans.destroy', $item) }}"
                                          onsubmit="return confirm('{{ __('wky.imbas.sahkan_padam', ['kod' => $item->kod]) }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-bahaya btn-ikon" title="{{ __('wky.aksi.padam') }}">
                                            <x-ikon nama="tong-sampah" kelas="size-4" />
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-10 text-center text-malap">
                            {!! __('wky.imbas.tiada_imbasan', ['butang' => '<strong class="text-teks">' . e(__('wky.imbas.imbas_baharu')) . '</strong>']) !!}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($imbasan->hasPages())
            <div class="kad-kaki penomboran block">{{ $imbasan->links() }}</div>
        @endif
    </div>
@endsection
