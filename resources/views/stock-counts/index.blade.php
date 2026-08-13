@extends('layouts.app')
@section('tajuk', __('wky.kiraan.tajuk'))

@section('kandungan')
    <div class="kad">
        <div class="kad-kepala">
            <span class="flex items-center gap-2 font-semibold">
                <x-ikon nama="papan-klip" kelas="size-5 text-aksen" />
                {{ __('wky.kiraan.tajuk_sesi') }}
            </span>
            <a href="{{ route('stock-counts.create') }}" class="btn-utama btn-kecil">
                <x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.kiraan.buka_sesi_baru') }}
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.kod') }}</th>
                        <th>{{ __('wky.medan.status') }}</th>
                        <th>{{ __('wky.medan.lokasi') }}</th>
                        <th>{{ __('wky.kiraan.skop') }}</th>
                        <th class="text-right">{{ __('wky.medan.produk') }}</th>
                        <th>{{ __('wky.dashboard.dibuka_oleh') }}</th>
                        <th>{{ __('wky.kiraan.tarikh_buka') }}</th>
                        <th>{{ __('wky.kiraan.disahkan') }}</th>
                        <th class="text-right">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($sesi as $item)
                    <tr>
                        <td><code>{{ $item->kod }}</code></td>
                        <td><span class="{{ $item->kelasStatus() }}">{{ $item->labelStatus() }}</span></td>
                        <td>{{ $item->location?->nama ?? __('wky.umum.kosong') }}</td>
                        <td>{{ $item->category?->nama ?? __('wky.umum.semua_kategori') }}</td>
                        <td class="text-right">{{ $item->items_count }}</td>
                        <td>{{ $item->pembuka?->name ?? __('wky.umum.kosong') }}</td>
                        <td class="whitespace-nowrap text-malap">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td class="whitespace-nowrap text-malap">{{ $item->disahkan_pada?->format('d/m/Y H:i') ?? __('wky.umum.kosong') }}</td>
                        <td>
                            <div class="flex justify-end">
                                <a href="{{ route('stock-counts.show', $item) }}" class="btn-garis btn-kecil">
                                    <x-ikon :nama="$item->isDraf() ? 'pensel' : 'mata'" kelas="size-4" />
                                    {{ $item->isDraf() ? __('wky.aksi.teruskan') : __('wky.aksi.lihat') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-10 text-center text-malap">
                            {!! __('wky.kiraan.tiada_sesi', ['butang' => '<strong class="text-teks">' . e(__('wky.kiraan.buka_sesi_baru')) . '</strong>']) !!}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($sesi->hasPages())
            <div class="kad-kaki penomboran block">{{ $sesi->links() }}</div>
        @endif
    </div>
@endsection
