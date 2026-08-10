@extends('layouts.app')
@section('tajuk', __('wky.pengguna.tajuk'))

@section('kandungan')
    <div class="card kad-stat">
        <div class="card-header d-flex justify-content-end">
            <a class="btn btn-primary" href="{{ route('users.create') }}"><i class="bi bi-plus-lg"></i> {{ __('wky.pengguna.tambah') }}</a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.nama') }}</th>
                        <th>{{ __('wky.medan.emel') }}</th>
                        <th>{{ __('wky.medan.peranan') }}</th>
                        <th>{{ __('wky.pengguna.didaftar') }}</th>
                        <th class="text-end">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($users as $pengguna)
                    <tr>
                        <td class="fw-medium">
                            {{ $pengguna->name }}
                            @if ($pengguna->is(auth()->user()))
                                <span class="badge bg-info ms-1">{{ __('wky.umum.anda') }}</span>
                            @endif
                        </td>
                        <td>{{ $pengguna->email }}</td>
                        <td><span class="badge bg-{{ $pengguna->isAdmin() ? 'primary' : 'secondary' }}">{{ __('wky.pengguna.' . $pengguna->peranan) }}</span></td>
                        <td class="small text-secondary">{{ $pengguna->created_at->format('d/m/Y') }}</td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('users.edit', $pengguna) }}" title="{{ __('wky.aksi.kemas_kini') }}"><i class="bi bi-pencil"></i></a>
                            @unless ($pengguna->is(auth()->user()))
                                <form class="d-inline" method="POST" action="{{ route('users.destroy', $pengguna) }}" onsubmit="return confirm('{{ __('wky.pengguna.sahkan_padam', ['nama' => $pengguna->name]) }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="{{ __('wky.aksi.padam') }}"><i class="bi bi-trash"></i></button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="card-footer">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
