@extends('layouts.app')
@section('tajuk', __('wky.pindah.tajuk_papar', ['kod' => $pemindahan->kod]))

@section('kandungan')
    <div class="kad">
        <div class="kad-kepala">
            <span class="flex flex-wrap items-center gap-2 font-semibold">
                <x-ikon nama="pindah" kelas="size-5 text-merah" />
                <code>{{ $pemindahan->kod }}</code>
                <span class="{{ $pemindahan->kelasStatus() }}">{{ $pemindahan->labelStatus() }}</span>
            </span>
            <a href="{{ route('transfers.index') }}" class="btn-garis btn-kecil">{{ __('wky.aksi.kembali') }}</a>
        </div>

        <dl class="kad-badan grid gap-3 border-b border-bingkai text-sm sm:grid-cols-4">
            <div>
                <dt class="text-malap">{{ __('wky.pindah.dari') }}</dt>
                <dd class="font-medium">{{ $pemindahan->asal?->nama }}</dd>
            </div>
            <div>
                <dt class="text-malap">{{ __('wky.pindah.ke') }}</dt>
                <dd class="font-medium">{{ $pemindahan->tujuan?->nama }}</dd>
            </div>
            <div>
                <dt class="text-malap">{{ __('wky.pindah.dihantar_oleh') }}</dt>
                <dd>{{ $pemindahan->penghantar?->name ?? __('wky.umum.kosong') }}</dd>
                <dd class="text-xs text-malap">{{ $pemindahan->created_at->format('d/m/Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-malap">{{ __('wky.pindah.diterima_oleh') }}</dt>
                <dd>{{ $pemindahan->penerima?->name ?? __('wky.umum.kosong') }}</dd>
                @if ($pemindahan->diterima_pada)
                    <dd class="text-xs text-malap">{{ $pemindahan->diterima_pada->format('d/m/Y H:i') }}</dd>
                @endif
            </div>
        </dl>

        @if ($pemindahan->dalamPerjalanan())
            <div class="kad-badan border-b border-bingkai">
                <div class="amaran-info">
                    <x-ikon nama="jam" kelas="size-5 shrink-0" />
                    <span>{{ __('wky.pindah.nota_dalam_perjalanan') }}</span>
                </div>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.sku') }}</th>
                        <th>{{ __('wky.medan.produk') }}</th>
                        <th class="text-right">{{ __('wky.medan.kuantiti') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($pemindahan->items as $item)
                    <tr>
                        <td><code>{{ $item->product?->sku }}</code></td>
                        <td>
                            <a href="{{ route('products.show', $item->product_id) }}" class="pautan-jadual">{{ $item->product?->nama }}</a>
                        </td>
                        <td class="text-right font-medium">{{ $item->kuantiti }} {{ $item->product?->unit }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">{{ __('wky.umum.jumlah') }}</td>
                        <td class="text-right">{{ $pemindahan->jumlahUnit() }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($pemindahan->catatan)
            <div class="kad-badan border-t border-bingkai text-sm text-malap">{{ $pemindahan->catatan }}</div>
        @endif

        @if ($pemindahan->dalamPerjalanan())
            <div class="kad-kaki">
                <form method="POST" action="{{ route('transfers.receive', $pemindahan) }}">
                    @csrf
                    <button type="submit" class="btn-utama">
                        <x-ikon nama="tanda-semak" kelas="size-4" /> {{ __('wky.pindah.terima') }}
                    </button>
                </form>

                {{-- Membatalkan memulangkan stok ke gudang asal, jadi ia bertanya dahulu. --}}
                <form method="POST" action="{{ route('transfers.destroy', $pemindahan) }}"
                      onsubmit="return confirm('{{ __('wky.pindah.sahkan_batal', ['kod' => $pemindahan->kod]) }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-bahaya">
                        <x-ikon nama="silang-bulat" kelas="size-4" /> {{ __('wky.pindah.batalkan') }}
                    </button>
                </form>
            </div>
        @endif
    </div>
@endsection
