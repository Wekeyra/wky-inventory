@extends('layouts.app')
@section('tajuk', __('wky.lokasi.tajuk'))

@section('kandungan')
    <div class="kad">
        <div class="kad-kepala">
            <span class="flex items-center gap-2 font-semibold">
                <x-ikon nama="gudang" kelas="size-5 text-aksen" />
                {{ __('wky.lokasi.tajuk') }}
            </span>
            <a href="{{ route('locations.create') }}" class="btn-utama btn-kecil">
                <x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.lokasi.tambah') }}
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.kod') }}</th>
                        <th>{{ __('wky.medan.nama') }}</th>
                        <th>{{ __('wky.medan.alamat') }}</th>
                        <th class="text-right">{{ __('wky.lokasi.bil_produk') }}</th>
                        <th class="text-right">{{ __('wky.lokasi.jumlah_unit') }}</th>
                        <th class="text-right">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($locations as $lokasi)
                    <tr>
                        <td><code>{{ $lokasi->kod }}</code></td>
                        <td>
                            <a href="{{ route('locations.show', $lokasi) }}" class="pautan-jadual">{{ $lokasi->nama }}</a>
                            @if ($lokasi->lalai)
                                <span class="lencana-biru ml-1">{{ __('wky.lokasi.lalai') }}</span>
                            @endif
                            @unless ($lokasi->aktif)
                                <span class="lencana-kelabu ml-1">{{ __('wky.umum.tidak_aktif') }}</span>
                            @endunless
                        </td>
                        <td class="text-malap">{{ $lokasi->alamat ?: __('wky.umum.kosong') }}</td>
                        <td class="text-right">{{ $lokasi->produk_count }}</td>
                        <td class="text-right font-medium">{{ number_format((int) $lokasi->jumlah_unit) }}</td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('transfers.create', ['location_id' => $lokasi->id]) }}" class="btn-garis btn-ikon" title="{{ __('wky.pindah.tambah') }}">
                                    <x-ikon nama="pindah" kelas="size-4" />
                                </a>
                                <a href="{{ route('locations.edit', $lokasi) }}" class="btn-garis btn-ikon" title="{{ __('wky.aksi.kemas_kini') }}">
                                    <x-ikon nama="pensel" kelas="size-4" />
                                </a>
                                {{-- Lokasi lalai dan lokasi yang masih ada stok tidak boleh dipadam; controller menahannya juga. --}}
                                @unless ($lokasi->lalai)
                                    <form method="POST" action="{{ route('locations.destroy', $lokasi) }}"
                                          onsubmit="return confirm('{{ __('wky.lokasi.sahkan_padam', ['nama' => $lokasi->nama]) }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-bahaya btn-ikon" title="{{ __('wky.aksi.padam') }}">
                                            <x-ikon nama="tong-sampah" kelas="size-4" />
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-malap">{{ __('wky.lokasi.tiada') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($locations->hasPages())
            <div class="kad-kaki penomboran block">{{ $locations->links() }}</div>
        @endif
    </div>
@endsection
