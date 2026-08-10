@extends('layouts.app')
@section('tajuk', 'Pengguna')

@section('kandungan')
    <div class="card kad-stat">
        <div class="card-header bg-white d-flex justify-content-end">
            <a class="btn btn-primary" href="{{ route('users.create') }}"><i class="bi bi-plus-lg"></i> Tambah Pengguna</a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Nama</th><th>Emel</th><th>Peranan</th><th>Didaftar</th><th class="text-end">Tindakan</th></tr>
                </thead>
                <tbody>
                @foreach ($users as $pengguna)
                    <tr>
                        <td class="fw-medium">
                            {{ $pengguna->name }}
                            @if ($pengguna->is(auth()->user()))
                                <span class="badge bg-info ms-1">Anda</span>
                            @endif
                        </td>
                        <td>{{ $pengguna->email }}</td>
                        <td><span class="badge bg-{{ $pengguna->isAdmin() ? 'primary' : 'secondary' }}">{{ ucfirst($pengguna->peranan) }}</span></td>
                        <td class="small text-muted">{{ $pengguna->created_at->format('d/m/Y') }}</td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('users.edit', $pengguna) }}"><i class="bi bi-pencil"></i></a>
                            @unless ($pengguna->is(auth()->user()))
                                <form class="d-inline" method="POST" action="{{ route('users.destroy', $pengguna) }}" onsubmit="return confirm('Padam pengguna {{ $pengguna->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="card-footer bg-white">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
