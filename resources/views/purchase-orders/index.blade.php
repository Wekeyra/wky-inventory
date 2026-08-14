@extends('layouts.app')
@section('tajuk', __('wky.po.tajuk'))

@section('kandungan')
    <div class="kad">
        <div class="kad-kepala">
            <form method="GET" class="flex w-full flex-wrap items-center gap-2">
                <select name="status" class="!w-auto min-w-48">
                    <option value="">{{ __('wky.po.semua_status') }}</option>
                    @foreach ($statusPilihan as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('status') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn-garis btn-ikon" title="{{ __('wky.aksi.cari') }}">
                    <x-ikon nama="cari" />
                </button>

                <a href="{{ route('purchase-orders.create') }}" class="btn-utama ml-auto">
                    <x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.po.tambah') }}
                </a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.kod') }}</th>
                        <th>{{ __('wky.medan.status') }}</th>
                        <th>{{ __('wky.po.pembekal') }}</th>
                        <th class="text-right">{{ __('wky.po.bil_produk') }}</th>
                        <th class="text-right">{{ __('wky.po.diterima') }}</th>
                        <th class="text-right">{{ __('wky.po.jumlah_nilai') }}</th>
                        <th>{{ __('wky.po.dipohon_oleh') }}</th>
                        <th>{{ __('wky.medan.tarikh') }}</th>
                        <th class="text-right">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($pesanan as $po)
                    <tr>
                        <td><code>{{ $po->kod }}</code></td>
                        <td><span class="{{ $po->kelasStatus() }}">{{ $po->labelStatus() }}</span></td>
                        <td class="text-malap">{{ $po->supplier?->nama ?? __('wky.umum.kosong') }}</td>
                        <td class="text-right">{{ $po->items->count() }}</td>
                        <td class="text-right whitespace-nowrap">
                            {{ $po->jumlahDiterima() }} / {{ $po->jumlahUnit() }}
                        </td>
                        <td class="text-right whitespace-nowrap">{{ number_format($po->jumlahNilai(), 2) }}</td>
                        <td class="text-malap">{{ $po->pemohon?->name ?? __('wky.umum.kosong') }}</td>
                        <td class="whitespace-nowrap text-malap">{{ $po->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="flex justify-end">
                                <a href="{{ route('purchase-orders.show', $po) }}" class="btn-garis btn-kecil">
                                    <x-ikon nama="mata" kelas="size-4" /> {{ __('wky.aksi.lihat') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-10 text-center text-malap">{{ __('wky.po.tiada') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($pesanan->hasPages())
            <div class="kad-kaki penomboran">{{ $pesanan->links() }}</div>
        @endif
    </div>
@endsection
