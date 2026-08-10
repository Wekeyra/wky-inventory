<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockCountController extends Controller
{
    public function index(): View
    {
        return view('stock-counts.index', [
            'sesi' => StockCount::query()
                ->with(['pembuka', 'category'])
                ->withCount('items')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('stock-counts.create', [
            'categories' => Category::withCount('products')->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'catatan' => ['nullable', 'string'],
        ]);

        $produk = Product::query()
            ->where('aktif', true)
            ->when($data['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->orderBy('nama')
            ->get(['id', 'stok']);

        if ($produk->isEmpty()) {
            return back()->withInput()->with('ralat', 'Tiada produk aktif untuk dikira dalam pilihan tersebut.');
        }

        $sesi = DB::transaction(function () use ($data, $request, $produk) {
            $sesi = StockCount::create([
                'kod' => $this->kodSeterusnya(),
                'status' => 'draf',
                'category_id' => $data['category_id'] ?? null,
                'dibuka_oleh' => $request->user()?->id,
                'catatan' => $data['catatan'] ?? null,
            ]);

            $sesi->items()->createMany(
                $produk->map(fn (Product $item) => [
                    'product_id' => $item->id,
                    'kuantiti_rekod' => $item->stok,
                ])->all()
            );

            return $sesi;
        });

        return redirect()->route('stock-counts.show', $sesi)
            ->with('status', "Sesi kiraan {$sesi->kod} dibuka dengan {$produk->count()} produk.");
    }

    public function show(StockCount $stockCount): View
    {
        $stockCount->load(['items.product.category', 'pembuka', 'pengesah', 'category']);

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

        return back()->with('status', "Kiraan disimpan sebagai draf ({$dikira} produk telah dikira).");
    }

    /** Mengesahkan sesi: setiap perbezaan menjana pergerakan stok jenis pelarasan. */
    public function confirm(Request $request, StockCount $stockCount): RedirectResponse
    {
        $this->pastikanDraf($stockCount);

        if ($stockCount->items()->whereNotNull('kuantiti_fizikal')->doesntExist()) {
            return back()->with('ralat', 'Masukkan sekurang-kurangnya satu kuantiti fizikal sebelum mengesahkan.');
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
                $selepas = $item->kuantiti_fizikal;

                if ($sebelum === $selepas) {
                    continue;
                }

                $product->update(['stok' => $selepas]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()?->id,
                    'jenis' => 'pelarasan',
                    'kuantiti' => $selepas,
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
            ->with('status', "Sesi {$stockCount->kod} disahkan. {$dilaraskan} produk dilaraskan.");
    }

    public function destroy(StockCount $stockCount): RedirectResponse
    {
        $this->pastikanDraf($stockCount);

        $stockCount->update(['status' => 'dibatalkan']);

        return redirect()->route('stock-counts.index')
            ->with('status', "Sesi {$stockCount->kod} dibatalkan. Tiada stok dilaraskan.");
    }

    private function pastikanDraf(StockCount $stockCount): void
    {
        if (! $stockCount->isDraf()) {
            throw ValidationException::withMessages([
                'status' => "Sesi {$stockCount->kod} sudah {$stockCount->labelStatus()} dan tidak boleh diubah lagi.",
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
