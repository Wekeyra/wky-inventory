@extends('layouts.app')
@section('tajuk', __('wky.kategori.tajuk'))

@section('kandungan')
    <div class="card kad-stat">
        <div class="card-header">
            <form class="row g-2 align-items-center" method="GET">
                <div class="col-md-5">
                    <input class="form-control" type="search" name="cari" value="{{ request('cari') }}" placeholder="{{ __('wky.kategori.cari_placeholder') }}">
                </div>
                <div class="col d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary" type="submit" title="{{ __('wky.aksi.cari') }}"><i class="bi bi-search"></i></button>
                    <a class="btn btn-primary" href="{{ route('categories.create') }}"><i class="bi bi-plus-lg"></i> {{ __('wky.aksi.tambah') }}</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('wky.medan.kod') }}</th>
                        <th>{{ __('wky.medan.nama') }}</th>
                        <th>{{ __('wky.medan.keterangan') }}</th>
                        <th class="text-end">{{ __('wky.medan.produk') }}</th>
                        <th class="text-end">{{ __('wky.medan.tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($categories as $kategori)
                    <tr>
                        <td><code>{{ $kategori->kod }}</code></td>
                        <td class="fw-medium">{{ $kategori->nama }}</td>
                        <td class="small text-secondary">{{ Str::limit($kategori->keterangan, 60) ?: __('wky.umum.kosong') }}</td>
                        <td class="text-end"><span class="badge bg-secondary">{{ $kategori->products_count }}</span></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('categories.edit', $kategori) }}" title="{{ __('wky.aksi.kemas_kini') }}"><i class="bi bi-pencil"></i></a>
                            <form class="d-inline" method="POST" action="{{ route('categories.destroy', $kategori) }}" onsubmit="return confirm('{{ __('wky.kategori.sahkan_padam', ['nama' => $kategori->nama]) }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit" title="{{ __('wky.aksi.padam') }}"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">{{ __('wky.kategori.tiada_dijumpai') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="card-footer">{{ $categories->links() }}</div>
        @endif
    </div>
@endsection
