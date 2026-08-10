{{--
    Borang ringkas untuk merekod stok terus dari dashboard.
    Ia menghantar ke laluan stock.store yang sama seperti borang penuh, jadi
    semua peraturan validasi dan kunci transaksi terpakai tanpa pertindihan logik.
--}}
<div class="modal fade" id="modalStokPantas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('stock.store') }}">
                @csrf
                <input type="hidden" name="sumber" value="pantas">

                <div class="modal-header border-secondary-subtle">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-1 text-danger"></i>{{ __('wky.dashboard.tambah_stok_pantas') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('wky.aksi.batal') }}"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="pantas_product_id">{{ __('wky.medan.produk') }} <span class="text-danger">*</span></label>
                        <select class="form-select" id="pantas_product_id" name="product_id" required>
                            <option value="">{{ __('wky.umum.pilih_produk') }}</option>
                            @foreach ($products as $produk)
                                <option value="{{ $produk->id }}" @selected(old('product_id') == $produk->id)>
                                    {{ $produk->nama }} ({{ $produk->sku }}) — {{ __('wky.stok.baki') }} {{ $produk->stok }} {{ $produk->unit }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label" for="pantas_jenis">{{ __('wky.medan.jenis') }} <span class="text-danger">*</span></label>
                            <select class="form-select" id="pantas_jenis" name="jenis" required>
                                <option value="masuk" @selected(old('jenis', 'masuk') === 'masuk')>{{ __('wky.stok.masuk') }}</option>
                                <option value="keluar" @selected(old('jenis') === 'keluar')>{{ __('wky.stok.keluar') }}</option>
                                <option value="pelarasan" @selected(old('jenis') === 'pelarasan')>{{ __('wky.stok.pelarasan') }}</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="pantas_kuantiti">{{ __('wky.medan.kuantiti') }} <span class="text-danger">*</span></label>
                            <input class="form-control" type="number" min="1" id="pantas_kuantiti" name="kuantiti" value="{{ old('kuantiti') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="pantas_rujukan">{{ __('wky.medan.rujukan') }}</label>
                            <input class="form-control" id="pantas_rujukan" name="rujukan" value="{{ old('rujukan') }}" placeholder="{{ __('wky.stok.rujukan_placeholder') }}">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-secondary-subtle">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{ __('wky.aksi.batal') }}</button>
                    <button class="btn btn-primary" type="submit">{{ __('wky.aksi.rekod') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if (old('sumber') === 'pantas')
    @push('skrip')
        {{-- Buka semula modal supaya pengguna nampak ralat pada borang yang sama. --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('modalStokPantas')).show();
            });
        </script>
    @endpush
@endif
