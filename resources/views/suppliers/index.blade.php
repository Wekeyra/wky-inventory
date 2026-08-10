@extends('layouts.app')
@section('tajuk', __('wky.pembekal.tajuk'))

@section('kandungan')
    <div class="card kad-stat">
        <div class="card-header">
            <form class="row g-2 align-items-center" method="GET">
                <div class="col-md-5">
                    <input class="form-control" type="search" name="cari" value="{{ $cari }}" placeholder="{{ __('wky.pembekal.cari_placeholder') }}">
                </div>
                <div class="col d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary" type="submit" title="{{ __('wky.aksi.cari') }}"><i class="bi bi-search"></i></button>
                    <a class="btn btn-primary" href="{{ route('suppliers.create') }}"><i class="bi bi-plus-lg"></i> {{ __('wky.aksi.tambah') }}</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.kod') }}</th>
                        <th>{{ __('wky.medan.nama') }}</th>
                        <th>{{ __('wky.medan.pegawai_perhubungan') }}</th>
                        <th>{{ __('wky.medan.telefon') }}</th>
                        <th class="text-end">{{ __('wky.medan.produk') }}</th>
                        <th>{{ __('wky.medan.status') }}</th>
                        <th class="text-end">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($suppliers as $pembekal)
                    <tr>
                        <td><code>{{ $pembekal->kod }}</code></td>
                        <td><a class="text-decoration-none fw-medium" href="{{ route('suppliers.show', $pembekal) }}">{{ $pembekal->nama }}</a></td>
                        <td>{{ $pembekal->pegawai_perhubungan ?? __('wky.umum.kosong') }}</td>
                        <td>{{ $pembekal->telefon ?? __('wky.umum.kosong') }}</td>
                        <td class="text-end"><span class="badge bg-secondary">{{ $pembekal->products_count }}</span></td>
                        <td><span class="badge bg-{{ $pembekal->aktif ? 'success' : 'secondary' }}">{{ $pembekal->aktif ? __('wky.umum.aktif') : __('wky.umum.tidak_aktif') }}</span></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('suppliers.edit', $pembekal) }}" title="{{ __('wky.aksi.kemas_kini') }}"><i class="bi bi-pencil"></i></a>
                            <form class="d-inline" method="POST" action="{{ route('suppliers.destroy', $pembekal) }}" onsubmit="return confirm('{{ __('wky.pembekal.sahkan_padam', ['nama' => $pembekal->nama]) }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit" title="{{ __('wky.aksi.padam') }}"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-4">{{ __('wky.pembekal.tiada_dijumpai') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($suppliers->hasPages())
            <div class="card-footer">{{ $suppliers->links() }}</div>
        @endif
    </div>
@endsection
