@extends('layouts.app')
@section('tajuk', __('wky.do.tajuk_penuh', ['no' => $pergerakan->no_do]))

@section('kandungan')
    <div class="kad kad-badan tanpa-cetak mb-4 flex flex-wrap gap-2">
        <button type="button" class="btn-utama" onclick="window.print()">
            <x-ikon nama="pencetak" kelas="size-4" /> {{ __('wky.aksi.cetak') }}
        </button>
        <a href="{{ route('stock.index') }}" class="btn-garis">{{ __('wky.aksi.kembali') }}</a>
    </div>

    <div class="kad kad-badan">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-bingkai pb-4">
            <div>
                {{-- Nama ruang kerja ialah nama syarikat yang mendaftar, jadi dokumen ini keluar atas namanya sendiri. --}}
                <h2 class="text-lg font-semibold text-teks">{{ auth()->user()->workspace?->nama }}</h2>
                <p class="text-sm text-malap">{{ __('wky.do.tajuk') }}</p>
            </div>

            <dl class="text-sm">
                <div class="flex gap-2">
                    <dt class="text-malap">{{ __('wky.do.no') }}:</dt>
                    <dd class="font-semibold"><code>{{ $pergerakan->no_do }}</code></dd>
                </div>
                <div class="flex gap-2">
                    <dt class="text-malap">{{ __('wky.medan.tarikh') }}:</dt>
                    <dd>{{ $pergerakan->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        <dl class="grid gap-3 border-b border-bingkai py-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-malap">{{ __('wky.medan.penerima') }}</dt>
                <dd class="font-medium">{{ $pergerakan->penerima ?: __('wky.umum.kosong') }}</dd>
            </div>
            <div>
                <dt class="text-malap">{{ __('wky.medan.rujukan') }}</dt>
                <dd>{{ $pergerakan->rujukan ?: __('wky.umum.kosong') }}</dd>
            </div>
            <div>
                <dt class="text-malap">{{ __('wky.medan.sebab') }}</dt>
                <dd>{{ $pergerakan->labelSebab() ?? __('wky.umum.kosong') }}</dd>
            </div>
            <div>
                <dt class="text-malap">{{ __('wky.do.dikeluarkan_oleh') }}</dt>
                <dd>{{ $pergerakan->user?->name ?? __('wky.umum.kosong') }}</dd>
            </div>
        </dl>

        <div class="overflow-x-auto py-4">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.sku') }}</th>
                        <th>{{ __('wky.medan.produk') }}</th>
                        <th>{{ __('wky.batch.no_batch') }}</th>
                        <th class="text-right">{{ __('wky.medan.kuantiti') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>{{ $pergerakan->product?->sku }}</code></td>
                        <td>{{ $pergerakan->product?->nama }}</td>
                        <td>{{ $pergerakan->batch?->no_batch ?? __('wky.umum.kosong') }}</td>
                        <td class="text-right font-medium">{{ $pergerakan->kuantiti }} {{ $pergerakan->product?->unit }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if ($pergerakan->catatan)
            <p class="border-t border-bingkai pt-4 text-sm text-malap">{{ $pergerakan->catatan }}</p>
        @endif

        {{--
            Satu DO membawa satu baris kerana ia dijana daripada satu pergerakan
            stok. Menggabungkan beberapa produk ke dalam satu dokumen bermakna
            satu dokumen merujuk beberapa rekod baki, dan itu memerlukan lapisan
            pesanan penghantaran yang tersendiri — kerja fasa Purchase Order.
        --}}
        <div class="mt-8 grid gap-8 sm:grid-cols-2">
            <div>
                <div class="h-12 border-b border-bingkai"></div>
                <p class="mt-2 text-xs text-malap">{{ __('wky.do.tandatangan_penghantar') }}</p>
            </div>
            <div>
                <div class="h-12 border-b border-bingkai"></div>
                <p class="mt-2 text-xs text-malap">{{ __('wky.do.tandatangan_penerima') }}</p>
            </div>
        </div>
    </div>
@endsection
