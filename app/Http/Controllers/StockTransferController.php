<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Services\Stok\BakiLokasi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Pemindahan stok antara gudang, dalam dua peringkat.
 *
 * Menghantar menolak baki gudang asal; menerima menambah baki gudang tujuan.
 * Antara kedua-duanya, kuantiti itu berada "dalam perjalanan" — masih dikira
 * dalam jumlah stok syarikat, tetapi tiada dalam baki mana-mana gudang.
 */
class StockTransferController extends Controller
{
    public function index(): View
    {
        return view('transfers.index', [
            'pemindahan' => StockTransfer::query()
                ->with(['asal', 'tujuan', 'penghantar'])
                ->withCount('items')
                ->withSum('items as jumlah_unit', 'kuantiti')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        return view('transfers.form', [
            'locations' => Location::aktif()->orderByDesc('lalai')->orderBy('nama')->get(),
            'products' => Product::where('aktif', true)
                ->with(['balances' => fn ($q) => $q->where('kuantiti', '>', 0)])
                ->orderBy('nama')
                ->get(),
            'asalTerpilih' => $request->integer('location_id') ?: Location::lalai()?->id,
        ]);
    }

    /** Menghantar: stok keluar dari gudang asal dan masuk ke dalam perjalanan. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_asal_id' => ['required', 'different:location_tujuan_id', $this->lokasiWujud($request)],
            'location_tujuan_id' => ['required', $this->lokasiWujud($request)],
            'catatan' => ['nullable', 'string'],
            'baris' => ['required', 'array'],
            'baris.*.product_id' => ['nullable', Rule::exists('products', 'id')
                ->where('workspace_id', $request->user()->workspace_id)],
            'baris.*.kuantiti' => ['nullable', 'integer', 'min:1'],
        ]);

        $baris = $this->barisTerisi($data['baris']);

        if ($baris === []) {
            return back()->withInput()->with('ralat', __('wky.flash.pindah_perlu_baris'));
        }

        try {
            $pemindahan = DB::transaction(function () use ($data, $baris, $request) {
                $pemindahan = StockTransfer::create([
                    'kod' => $this->kodSeterusnya(),
                    'status' => 'dalam_perjalanan',
                    'location_asal_id' => $data['location_asal_id'],
                    'location_tujuan_id' => $data['location_tujuan_id'],
                    'dihantar_oleh' => $request->user()?->id,
                    'catatan' => $data['catatan'] ?? null,
                ]);

                foreach ($baris as $satu) {
                    $product = Product::lockForUpdate()->findOrFail($satu['product_id']);

                    BakiLokasi::laraskan($product, (int) $data['location_asal_id'], -$satu['kuantiti']);

                    $pemindahan->items()->create([
                        'product_id' => $product->id,
                        'kuantiti' => $satu['kuantiti'],
                    ]);

                    $this->rekod($pemindahan, $product, $satu['kuantiti'], 'pindah_hantar', $request);
                }

                return $pemindahan;
            });
        } catch (RuntimeException $e) {
            return back()->withInput()->with('ralat', $e->getMessage());
        }

        return redirect()->route('transfers.show', $pemindahan)
            ->with('status', __('wky.flash.pindah_dihantar', ['kod' => $pemindahan->kod]));
    }

    public function show(StockTransfer $transfer): View
    {
        $transfer->load(['items.product', 'asal', 'tujuan', 'penghantar', 'penerima']);

        return view('transfers.show', ['pemindahan' => $transfer]);
    }

    /** Menerima: stok keluar dari perjalanan dan masuk ke baki gudang tujuan. */
    public function receive(Request $request, StockTransfer $transfer): RedirectResponse
    {
        $this->pastikanDalamPerjalanan($transfer);

        DB::transaction(function () use ($transfer, $request) {
            foreach ($transfer->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                if ($product === null) {
                    continue;
                }

                BakiLokasi::laraskan($product, $transfer->location_tujuan_id, $item->kuantiti);

                $this->rekod($transfer, $product, $item->kuantiti, 'pindah_terima', $request);
            }

            $transfer->update([
                'status' => 'selesai',
                'diterima_oleh' => $request->user()?->id,
                'diterima_pada' => now(),
            ]);
        });

        return redirect()->route('transfers.show', $transfer)
            ->with('status', __('wky.flash.pindah_diterima', ['kod' => $transfer->kod]));
    }

    /**
     * Membatalkan penghantaran yang belum diterima; stok kembali ke gudang asal.
     *
     * Pemindahan yang sudah diterima tidak boleh dibatalkan — barangnya sudah
     * berada di gudang tujuan, dan memulangkannya ialah satu lagi pemindahan
     * yang berhak mendapat rekodnya sendiri.
     */
    public function destroy(Request $request, StockTransfer $transfer): RedirectResponse
    {
        $this->pastikanDalamPerjalanan($transfer);

        DB::transaction(function () use ($transfer, $request) {
            foreach ($transfer->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                if ($product === null) {
                    continue;
                }

                BakiLokasi::laraskan($product, $transfer->location_asal_id, $item->kuantiti);

                $this->rekod($transfer, $product, $item->kuantiti, 'pindah_batal', $request);
            }

            $transfer->update(['status' => 'dibatalkan']);
        });

        return redirect()->route('transfers.show', $transfer)
            ->with('status', __('wky.flash.pindah_dibatalkan', ['kod' => $transfer->kod]));
    }

    /**
     * Merekod satu peringkat pemindahan dalam jejak audit.
     *
     * `stok_sebelum` dan `stok_selepas` adalah sama kerana jumlah stok syarikat
     * memang tidak berubah — yang berubah ialah gudang tempat barang itu
     * berada, dan itu dibaca daripada lokasi asal dan tujuan pada baris ini.
     *
     * `kos_seunit` sengaja dibiarkan kosong atas sebab yang sama. Memindahkan
     * barang antara gudang sendiri bukan peristiwa kos: tiada apa yang dibeli
     * dan tiada apa yang digunakan. Mengecapkan kos di sini akan menjadikan
     * jumlah kos laporan berganda setiap kali barang berpindah rak.
     */
    private function rekod(StockTransfer $pemindahan, Product $product, int $kuantiti, string $sebab, Request $request): void
    {
        StockMovement::create([
            'product_id' => $product->id,
            'location_id' => $pemindahan->location_asal_id,
            'location_tujuan_id' => $pemindahan->location_tujuan_id,
            'user_id' => $request->user()?->id,
            'jenis' => 'pindah',
            'sebab' => $sebab,
            'kuantiti' => $kuantiti,
            'stok_sebelum' => $product->stok,
            'stok_selepas' => $product->stok,
            'rujukan' => $pemindahan->kod,
        ]);
    }

    /**
     * Baris yang benar-benar diisi, dengan produk berulang digabungkan.
     *
     * Borang membenarkan baris ditambah sesuka hati, jadi produk yang sama
     * boleh dipilih dua kali. Menggabungkannya di sini menjadikan pemindahan
     * membawa satu baris bagi satu produk — itulah yang dijangka oleh kekangan
     * unik pada jadual, dan juga oleh sesiapa yang membaca senarai itu.
     *
     * @param  array<int, array<string, mixed>>  $baris
     * @return array<int, array{product_id: int, kuantiti: int}>
     */
    private function barisTerisi(array $baris): array
    {
        $gabung = [];

        foreach ($baris as $satu) {
            if (empty($satu['product_id']) || empty($satu['kuantiti'])) {
                continue;
            }

            $id = (int) $satu['product_id'];
            $gabung[$id] = ($gabung[$id] ?? 0) + (int) $satu['kuantiti'];
        }

        return array_map(
            fn (int $id, int $kuantiti) => ['product_id' => $id, 'kuantiti' => $kuantiti],
            array_keys($gabung),
            $gabung,
        );
    }

    private function lokasiWujud(Request $request): Exists
    {
        return Rule::exists('locations', 'id')
            ->where('workspace_id', $request->user()->workspace_id)
            ->where('aktif', true);
    }

    private function pastikanDalamPerjalanan(StockTransfer $transfer): void
    {
        if (! $transfer->dalamPerjalanan()) {
            throw ValidationException::withMessages([
                'status' => __('wky.flash.pindah_terkunci', [
                    'kod' => $transfer->kod,
                    'status' => mb_strtolower($transfer->labelStatus()),
                ]),
            ]);
        }
    }

    private function kodSeterusnya(): string
    {
        $tahun = now()->format('Y');
        $bil = StockTransfer::where('kod', 'like', "PDH-{$tahun}-%")->count() + 1;

        return sprintf('PDH-%s-%03d', $tahun, $bil);
    }
}
