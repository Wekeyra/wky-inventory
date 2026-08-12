@extends('layouts.app')
@section('tajuk', __('wky.pengguna.tajuk'))

@section('kandungan')
    @if ($bilMenunggu > 0)
        <div class="amaran-info mb-4">
            <x-ikon nama="jam" kelas="size-5 shrink-0" />
            <span>{{ __('wky.pengguna.menunggu_kelulusan', ['bil' => $bilMenunggu]) }}</span>
        </div>
    @endif

    <div class="kad">
        <div class="kad-kepala justify-end">
            <a href="{{ route('users.create') }}" class="btn-utama"><x-ikon nama="tambah" kelas="size-4" /> {{ __('wky.pengguna.tambah') }}</a>
        </div>

        <div class="overflow-x-auto">
            <table class="jadual">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.nama') }}</th>
                        <th>{{ __('wky.medan.emel') }}</th>
                        <th>{{ __('wky.medan.peranan') }}</th>
                        <th>{{ __('wky.medan.status') }}</th>
                        <th>{{ __('wky.pengguna.didaftar') }}</th>
                        <th class="text-right">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($users as $pengguna)
                    <tr>
                        <td class="font-medium">
                            {{ $pengguna->name }}
                            @if ($pengguna->is(auth()->user()))
                                <span class="lencana-biru ml-1">{{ __('wky.umum.anda') }}</span>
                            @endif
                        </td>
                        <td class="break-all">
                            {{ $pengguna->email }}
                            @if ($pengguna->google_id)
                                <span class="lencana-kelabu ml-1" title="{{ __('wky.pengguna.akaun_google') }}">Google</span>
                            @endif
                        </td>
                        <td>
                            <span class="{{ $pengguna->isAdmin() ? 'lencana-merah' : 'lencana-kelabu' }}">
                                {{ __('wky.pengguna.' . $pengguna->peranan) }}
                            </span>
                        </td>
                        <td>
                            @php
                                $lencanaStatus = match ($pengguna->status) {
                                    'aktif' => 'lencana-hijau',
                                    'menunggu' => 'lencana-kuning',
                                    default => 'lencana-merah',
                                };
                            @endphp
                            <span class="{{ $lencanaStatus }}">{{ __('wky.pengguna.status_' . $pengguna->status) }}</span>
                        </td>
                        <td class="whitespace-nowrap text-malap">{{ $pengguna->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="flex justify-end gap-1">
                                @unless ($pengguna->isAktif())
                                    <form method="POST" action="{{ route('users.luluskan', $pengguna) }}"
                                          onsubmit="return confirm('{{ __('wky.pengguna.sahkan_luluskan', ['nama' => $pengguna->name]) }}')">
                                        @csrf
                                        <button type="submit" class="btn-utama btn-kecil">
                                            <x-ikon nama="tanda-semak" kelas="size-4" />
                                            {{ __('wky.pengguna.luluskan') }}
                                        </button>
                                    </form>
                                @endunless

                                @if ($pengguna->isMenunggu() && ! $pengguna->is(auth()->user()))
                                    <form method="POST" action="{{ route('users.tolak', $pengguna) }}"
                                          onsubmit="return confirm('{{ __('wky.pengguna.sahkan_tolak', ['nama' => $pengguna->name]) }}')">
                                        @csrf
                                        <button type="submit" class="btn-garis btn-kecil">
                                            <x-ikon nama="silang-bulat" kelas="size-4" />
                                            {{ __('wky.pengguna.tolak') }}
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('users.edit', $pengguna) }}" class="btn-garis btn-ikon" title="{{ __('wky.aksi.kemas_kini') }}">
                                    <x-ikon nama="pensel" kelas="size-4" />
                                </a>
                                @unless ($pengguna->is(auth()->user()))
                                    <form method="POST" action="{{ route('users.destroy', $pengguna) }}"
                                          onsubmit="return confirm('{{ __('wky.pengguna.sahkan_padam', ['nama' => $pengguna->name]) }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-bahaya btn-ikon" title="{{ __('wky.aksi.padam') }}">
                                            <x-ikon nama="tong-sampah" kelas="size-4" />
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="kad-kaki penomboran block">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
