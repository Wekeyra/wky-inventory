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
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-1 text-danger"></i>Tambah Stok Pantas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="pantas_product_id">Produk <span class="text-danger">*</span></label>
                        <select class="form-select" id="pantas_product_id" name="product_id" required>
                            <option value="">— Pilih produk —</option>
                            @foreach ($products as $produk)
                                <option value="{{ $produk->id }}" @selected(old('product_id') == $produk->id)>
                                    {{ $produk->nama }} ({{ $produk->sku }}) — baki {{ $produk->stok }} {{ $produk->unit }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label" for="pantas_jenis">Jenis <span class="text-danger">*</span></label>
                            <select class="form-select" id="pantas_jenis" name="jenis" required>
                                <option value="masuk" @selected(old('jenis', 'masuk') === 'masuk')>Stok Masuk</option>
                                <option value="keluar" @selected(old('jenis') === 'keluar')>Stok Keluar</option>
                                <option value="pelarasan" @selected(old('jenis') === 'pelarasan')>Pelarasan</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="pantas_kuantiti">Kuantiti <span class="text-danger">*</span></label>
                            <input class="form-control" type="number" min="1" id="pantas_kuantiti" name="kuantiti" value="{{ old('kuantiti') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="pantas_rujukan">Rujukan</label>
                            <input class="form-control" id="pantas_rujukan" name="rujukan" value="{{ old('rujukan') }}" placeholder="Cth: PO-1234">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-secondary-subtle">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" type="submit">Rekod</button>
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
