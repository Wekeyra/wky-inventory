@extends('layouts.app')
@section('tajuk', __('wky.pengguna.tajuk'))

@section('kandungan')
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
                            <span class="{{ $pengguna->isAdmin() ? 'lencana-aksen' : 'lencana-kelabu' }}">
                                {{ __('wky.pengguna.' . $pengguna->peranan) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap text-malap">{{ $pengguna->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="flex justify-end gap-1">
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
