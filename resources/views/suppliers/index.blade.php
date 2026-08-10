@extends('layouts.app')
@section('tajuk', 'Pembekal')

@section('kandungan')
    <div class="card kad-stat">
        <div class="card-header bg-white">
            <form class="row g-2 align-items-center" method="GET">
                <div class="col-md-5">
                    <input class="form-control" type="search" name="cari" value="{{ $cari }}" placeholder="Cari nama, kod atau pegawai…">
                </div>
                <div class="col d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    <a class="btn btn-primary" href="{{ route('suppliers.create') }}"><i class="bi bi-plus-lg"></i> Tambah</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Kod</th><th>Nama</th><th>Pegawai Perhubungan</th><th>Telefon</th><th class="text-end">Produk</th><th>Status</th><th class="text-end">Tindakan</th></tr>
                </thead>
                <tbody>
                @forelse ($suppliers as $pembekal)
                    <tr>
                        <td><code>{{ $pembekal->kod }}</code></td>
                        <td><a class="text-decoration-none fw-medium" href="{{ route('suppliers.show', $pembekal) }}">{{ $pembekal->nama }}</a></td>
                        <td>{{ $pembekal->pegawai_perhubungan ?? '—' }}</td>
                        <td>{{ $pembekal->telefon ?? '—' }}</td>
                        <td class="text-end"><span class="badge bg-secondary">{{ $pembekal->products_count }}</span></td>
                        <td><span class="badge bg-{{ $pembekal->aktif ? 'success' : 'secondary' }}">{{ $pembekal->aktif ? 'Aktif' : 'Tidak aktif' }}</span></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('suppliers.edit', $pembekal) }}"><i class="bi bi-pencil"></i></a>
                            <form class="d-inline" method="POST" action="{{ route('suppliers.destroy', $pembekal) }}" onsubmit="return confirm('Padam pembekal {{ $pembekal->nama }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Tiada pembekal dijumpai.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($suppliers->hasPages())
            <div class="card-footer bg-white">{{ $suppliers->links() }}</div>
        @endif
    </div>
@endsection
