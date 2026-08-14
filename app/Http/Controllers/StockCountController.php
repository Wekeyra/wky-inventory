<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Services\Stok\BakiLokasi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockCountController extends Controller
{
    public function index(): View
    {
        return view('stock-counts.index', [
            'sesi' => StockCount::query()
                ->with(['pembuka', 'category', 'location'])
                ->withCount('items')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('stock-counts.create', [
            'categories' => Category::withCount('products')->orderBy('nama')->get(),
            'locations' => Location::aktif()->orderByDesc('lalai')->orderBy('nama')->get(),
            'lokasiLalai' => Location::lalai()?->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', Rule::exists('categories', 'id')
                ->where('workspace_id', $request->user()->workspace_id)],
            // Kiraan fizikal berlaku di satu gudang: seseorang berdiri di situ
            // dan membilang rak. Sesi yang merangkumi semua gudang sekali gus
            // tidak boleh dilakukan sesiapa, dan pelarasannya tidak dapat
            // memberitahu gudang mana yang sebenarnya kurang.
            'location_id' => ['nullable', Rule::exists('locations', 'id')
                ->where('workspace_id', $request->user()->workspace_id)
                ->where('aktif', true)],
            'catatan' => ['nullable', 'string'],
        ]);

        $lokasi = (int) ($data['location_id'] ?? Location::lalai()?->id);

        $produk = Product::query()
            ->where('aktif', true)
            ->when($data['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->with(['balances' => fn ($q) => $q->where('location_id', $lokasi)])
            ->orderBy('nama')
            ->get(['id', 'stok']);

        if ($produk->isEmpty()) {
            return back()->withInput()->with('ralat', __('wky.flash.kiraan_tiada_produk'));
        }

        $sesi = DB::transaction(function () use ($data, $request, $produk, $lokasi) {
            $sesi = StockCount::create([
                'kod' => $this->kodSeterusnya(),
                'status' => 'draf',
                'category_id' => $data['category_id'] ?? null,
                'location_id' => $lokasi,
                'dibuka_oleh' => $request->user()?->id,
                'catatan' => $data['catatan'] ?? null,
            ]);

            $sesi->items()->createMany(
                // Gambaran yang disimpan ialah baki gudang itu, bukan jumlah
                // keseluruhan produk — itulah yang sepatutnya sepadan dengan
                // apa yang dilihat di rak.
                $produk->map(fn (Product $item) => [
                    'product_id' => $item->id,
                    'kuantiti_rekod' => (int) $item->balances->firstWhere('location_id', $lokasi)?->kuantiti,
                ])->all()
            );

            return $sesi;
        });

        return redirect()->route('stock-counts.show', $sesi)
            ->with('status', __('wky.flash.kiraan_dibuka', ['kod' => $sesi->kod, 'bil' => $produk->count()]));
    }

    public function show(StockCount $stockCount): View
    {
        $stockCount->load(['items.product.category', 'pembuka', 'pengesah', 'category', 'location']);

        return view('stock-counts.show', ['sesi' => $stockCount]);
    }

    /** Menyimpan kuantiti fizikal tanpa melaraskan stok, supaya kiraan boleh dibuat berperingkat. */
    public function update(Request $request, StockCount $stockCount): RedirectResponse
    {
        $this->pastikanDraf($stockCount);

        $data = $request->validate([
            'kuantiti' => ['array'],
            'kuantiti.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $dikira = 0;

        DB::transaction(function () use ($stockCount, $data, &$dikira) {
            foreach ($stockCount->items as $item) {
                if (! array_key_exists($item->id, $data['kuantiti'] ?? [])) {
                    continue;
                }

                $nilai = $data['kuantiti'][$item->id];
                $item->update(['kuantiti_fizikal' => $nilai === null || $nilai === '' ? null : (int) $nilai]);

                if ($item->kuantiti_fizikal !== null) {
                    $dikira++;
                }
            }
        });

        return back()->with('status', __('wky.flash.kiraan_draf_disimpan', ['bil' => $dikira]));
    }

    /** Mengesahkan sesi: setiap perbezaan menjana pergerakan stok jenis pelarasan. */
    public function confirm(Request $request, StockCount $stockCount): RedirectResponse
    {
        $this->pastikanDraf($stockCount);

        if ($stockCount->items()->whereNotNull('kuantiti_fizikal')->doesntExist()) {
            return back()->with('ralat', __('wky.flash.kiraan_perlu_kuantiti'));
        }

        $dilaraskan = 0;

        DB::transaction(function () use ($stockCount, $request, &$dilaraskan) {
            $items = $stockCount->items()->whereNotNull('kuantiti_fizikal')->get();

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                if ($product === null) {
                    continue;
                }

                // Baki dibaca semula di sini, bukan daripada gambaran sesi, kerana stok
                // mungkin telah berubah antara pembukaan sesi dan pengesahan.
                $sebelum = $product->stok;
                $bezaLokasi = BakiLokasi::tetapkan($product, $stockCount->location_id, $item->kuantiti_fizikal);

                if ($bezaLokasi === 0) {
                    continue;
                }

                // Jumlah produk bergerak sebanyak perbezaan di gudang ini
                // sahaja; baki gudang lain tidak dibilang dalam sesi ini dan
                // tidak sepatutnya terjejas olehnya.
                $selepas = $sebelum + $bezaLokasi;

                $product->update(['stok' => $selepas]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'location_id' => $stockCount->location_id,
                    'user_id' => $request->user()?->id,
                    'jenis' => 'pelarasan',
                    'sebab' => 'kiraan_fizikal',
                    'kuantiti' => $item->kuantiti_fizikal,
                    // Kiraan fizikal yang mendapati stok kurang ialah kerugian
                    // sebenar, jadi ia perlu membawa nilai. Harga kos semasa
                    // produk ialah anggaran terbaik yang ada di sini; sifar
                    // dibiarkan sebagai "tidak direkod" kerana harga kos yang
                    // tidak pernah ditetapkan bukan bermakna barang itu percuma.
                    'kos_seunit' => (float) $product->harga_kos > 0 ? (float) $product->harga_kos : null,
                    'stok_sebelum' => $sebelum,
                    'stok_selepas' => $selepas,
                    'rujukan' => $stockCount->kod,
                    'catatan' => 'Pelarasan hasil kiraan stok fizikal.',
                ]);

                $dilaraskan++;
            }

            $stockCount->update([
                'status' => 'selesai',
                'disahkan_oleh' => $request->user()?->id,
                'disahkan_pada' => now(),
            ]);
        });

        return redirect()->route('stock-counts.show', $stockCount)
            ->with('status', __('wky.flash.kiraan_disahkan', ['kod' => $stockCount->kod, 'bil' => $dilaraskan]));
    }

    public function destroy(StockCount $stockCount): RedirectResponse
    {
        $this->pastikanDraf($stockCount);

        $stockCount->update(['status' => 'dibatalkan']);

        return redirect()->route('stock-counts.index')
            ->with('status', __('wky.flash.kiraan_dibatalkan', ['kod' => $stockCount->kod]));
    }

    private function pastikanDraf(StockCount $stockCount): void
    {
        if (! $stockCount->isDraf()) {
            throw ValidationException::withMessages([
                'status' => __('wky.flash.kiraan_terkunci', [
                    'kod' => $stockCount->kod,
                    'status' => mb_strtolower($stockCount->labelStatus()),
                ]),
            ]);
        }
    }

    private function kodSeterusnya(): string
    {
        $tahun = now()->format('Y');
        $bil = StockCount::where('kod', 'like', "KIRA-{$tahun}-%")->count() + 1;

        return sprintf('KIRA-%s-%03d', $tahun, $bil);
    }
}
