<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Services\Stok\BakiLokasi;
use App\Services\Stok\KosKeluar;
use App\Services\Stok\LotKeluar;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Jualan, dan kos barang dijual.
 *
 * Setiap baris jualan membekukan dua harga: harga jual yang dibayar pelanggan,
 * dan kos barang itu pada masa ia keluar. Untung kasar ialah perbezaan antara
 * kedua-duanya, dikira daripada angka yang dibekukan dan bukan daripada harga
 * produk yang dibaca semula semasa laporan dibuka — kedua-dua harga itu boleh
 * berubah selepas jualan berlaku.
 *
 * Jualan tidak menyentuh stok secara langsung. Setiap barisnya menjana satu
 * pergerakan stok keluar dengan sebab "jualan", melalui perkhidmatan baki dan
 * lot yang sama seperti aliran stok yang lain — jadi tiada satu aliran pun
 * terlepas semakan bakinya sendiri.
 */
class SaleController extends Controller
{
    public function index(): View
    {
        return view('sales.index', [
            'jualan' => Sale::query()
                ->with(['items', 'location', 'user'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        return view('sales.form', [
            'products' => Product::where('aktif', true)
                ->with([
                    'batches' => fn ($q) => $q->adaBaki()->orderByRaw('tarikh_luput is null, tarikh_luput'),
                ])
                ->orderBy('nama')
                ->get(),
            'locations' => Location::aktif()->orderByDesc('lalai')->orderBy('nama')->get(),
            'lokasiTerpilih' => $request->integer('location_id') ?: Location::lalai()?->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pelanggan' => ['nullable', 'string', 'max:255'],
            'location_id' => ['nullable', Rule::exists('locations', 'id')
                ->where('workspace_id', $request->user()->workspace_id)
                ->where('aktif', true)],
            'catatan' => ['nullable', 'string'],
            'baris' => ['required', 'array'],
            'baris.*.product_id' => ['nullable', Rule::exists('products', 'id')
                ->where('workspace_id', $request->user()->workspace_id)],
            'baris.*.kuantiti' => ['nullable', 'integer', 'min:1'],
            'baris.*.harga_jual' => ['nullable', 'numeric', 'min:0'],
            'baris.*.product_batch_id' => ['nullable', Rule::exists('product_batches', 'id')
                ->where('workspace_id', $request->user()->workspace_id)],
        ]);

        $baris = $this->barisTerisi($data['baris']);

        if ($baris === []) {
            return back()->withInput()->with('ralat', __('wky.flash.jual_perlu_baris'));
        }

        try {
            $jualan = DB::transaction(function () use ($data, $baris, $request) {
                $lokasi = (int) ($data['location_id'] ?? Location::lalai()?->id);

                $jualan = Sale::create([
                    'kod' => $this->kodSeterusnya(),
                    'pelanggan' => $data['pelanggan'] ?? null,
                    'location_id' => $lokasi,
                    'user_id' => $request->user()?->id,
                    'catatan' => $data['catatan'] ?? null,
                ]);

                foreach ($baris as $satu) {
                    $this->jual($jualan, $satu, $lokasi, $request);
                }

                return $jualan;
            });
        } catch (RuntimeException $e) {
            return back()->withInput()->with('ralat', $e->getMessage());
        }

        return redirect()->route('sales.show', $jualan)
            ->with('status', __('wky.flash.jual_direkod', ['kod' => $jualan->kod]));
    }

    public function show(Sale $sale): View
    {
        $sale->load(['items.product', 'items.batch', 'location', 'user']);

        return view('sales.show', ['jualan' => $sale]);
    }

    /**
     * Satu baris jualan: stok ditolak, kos dibekukan, pergerakan direkod.
     *
     * @param  array{product_id: int, kuantiti: int, harga_jual: float|null, product_batch_id: int|null}  $satu
     */
    private function jual(Sale $jualan, array $satu, int $lokasi, Request $request): void
    {
        // lockForUpdate menghalang dua jualan menolak baki yang sama serentak.
        $product = Product::lockForUpdate()->findOrFail($satu['product_id']);

        // Produk yang dijejak batchnya mesti menyebut lot mana yang keluar.
        // Tanpa itu, baki lot akan tertinggal di belakang baki produk dan
        // angka batch menjadi tidak boleh dipercayai.
        if ($product->jejak_batch && $satu['product_batch_id'] === null) {
            throw new RuntimeException(__('wky.flash.jual_perlu_lot', ['produk' => $product->nama]));
        }

        $sebelum = $product->stok;
        $selepas = $sebelum - $satu['kuantiti'];

        if ($selepas < 0) {
            throw new RuntimeException(__('wky.flash.stok_tidak_cukup', [
                'baki' => $product->stok,
                'unit' => $product->unit,
            ]));
        }

        // Baki gudang disemak oleh BakiLokasi, yang menolak penolakan melebihi
        // baki gudang itu — satu gudang tidak boleh menjual barang yang
        // sebenarnya berada di gudang lain.
        BakiLokasi::laraskan($product, $lokasi, -$satu['kuantiti']);

        $lot = null;

        if ($satu['product_batch_id'] !== null) {
            $lot = LotKeluar::ambil(
                ProductBatch::findOrFail($satu['product_batch_id']),
                $satu['kuantiti'],
                $product,
            );
        }

        $product->update(['stok' => $selepas]);

        // Harga jual yang dibiarkan kosong mengambil harga jual produk. Sifar
        // yang ditaip sendiri kekal sifar: memberi barang percuma ialah satu
        // keputusan, bukan medan yang terlepas.
        $harga = $satu['harga_jual'] ?? (float) $product->harga_jual;
        $kos = KosKeluar::bagi($product, $lot);

        $jualan->items()->create([
            'product_id' => $product->id,
            'product_batch_id' => $lot?->id,
            'kuantiti' => $satu['kuantiti'],
            'harga_jual' => $harga,
            'kos_seunit' => $kos,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'product_batch_id' => $lot?->id,
            'location_id' => $lokasi,
            'user_id' => $request->user()?->id,
            'jenis' => 'keluar',
            'sebab' => 'jualan',
            'kuantiti' => $satu['kuantiti'],
            'kos_seunit' => $kos,
            'stok_sebelum' => $sebelum,
            'stok_selepas' => $selepas,
            'rujukan' => $jualan->kod,
            // Tiada nombor DO di sini. Satu jualan menghantar banyak baris
            // dalam satu penghantaran, jadi memberi setiap baris nombor DO
            // sendiri akan menghasilkan sekumpulan dokumen penghantaran bagi
            // penghantaran yang sama. Jualan itu sendiri ialah dokumennya.
            'catatan' => __('wky.jual.catatan_pergerakan', ['kod' => $jualan->kod]),
        ]);
    }

    /**
     * Baris yang benar-benar diisi.
     *
     * Berbeza daripada PO dan pemindahan, produk berulang **tidak** digabungkan:
     * dua baris produk yang sama boleh membawa harga jual berbeza — diskaun
     * pada sebahagian kuantiti ialah jualan yang sah, dan menggabungkannya akan
     * membuang salah satu harga itu.
     *
     * @param  array<int, array<string, mixed>>  $baris
     * @return array<int, array{product_id: int, kuantiti: int, harga_jual: float|null, product_batch_id: int|null}>
     */
    private function barisTerisi(array $baris): array
    {
        $terisi = [];

        foreach ($baris as $satu) {
            $produk = (int) ($satu['product_id'] ?? 0);
            $kuantiti = (int) ($satu['kuantiti'] ?? 0);

            if ($produk === 0 || $kuantiti <= 0) {
                continue;
            }

            $harga = ($satu['harga_jual'] ?? '') === '' ? null : (float) $satu['harga_jual'];
            $lot = (int) ($satu['product_batch_id'] ?? 0);

            $terisi[] = [
                'product_id' => $produk,
                'kuantiti' => $kuantiti,
                'harga_jual' => $harga,
                'product_batch_id' => $lot ?: null,
            ];
        }

        return $terisi;
    }

    private function kodSeterusnya(): string
    {
        $tahun = now()->format('Y');
        $bil = Sale::where('kod', 'like', "JL-{$tahun}-%")->count() + 1;

        return sprintf('JL-%s-%03d', $tahun, $bil);
    }
}
