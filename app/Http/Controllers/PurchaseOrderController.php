<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\Stok\BakiLokasi;
use App\Services\Stok\LotPenerimaan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Permohonan pembelian, kelulusan, dan penerimaan barang.
 *
 * Satu rekod melalui keseluruhan aliran: draf → menunggu → diluluskan →
 * selesai. Permohonan yang diluluskan *menjadi* PO dan bukan disalin menjadi
 * dokumen kedua, kerana dua dokumen tentang barang yang sama akan terpesong
 * sebaik sahaja seseorang menyunting salah satunya.
 *
 * Kos yang diluluskan pada setiap baris ialah kos yang dicap pada pergerakan
 * stok semasa barang diterima. Harga yang diluluskan itulah yang masuk ke dalam
 * kira-kira — bukan harga kos produk yang mungkin sudah berubah antara
 * kelulusan dan penghantaran.
 */
class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('purchase-orders.index', [
            'pesanan' => PurchaseOrder::query()
                ->with(['supplier', 'pemohon', 'items'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'statusPilihan' => self::statusPilihan(),
        ]);
    }

    /**
     * Status yang boleh ditapis, berpasangan dengan labelnya.
     *
     * @return array<string, string>
     */
    public static function statusPilihan(): array
    {
        return collect(array_keys(PurchaseOrder::PERALIHAN))
            ->mapWithKeys(fn (string $status) => [$status => __('wky.po.status_'.$status)])
            ->all();
    }

    public function create(Request $request): View
    {
        return view('purchase-orders.form', [
            'pesanan' => new PurchaseOrder(),
            'suppliers' => Supplier::orderBy('nama')->get(),
            'products' => Product::where('aktif', true)->orderBy('nama')->get(),
            'awal' => $this->barisAwal($request),
        ]);
    }

    /**
     * Baris permulaan daripada cadangan reorder halaman Analitik.
     *
     * Bentuknya `?produk[ID]=KUANTITI`. Produk disaring terhadap ruang kerja
     * pengguna, kerana ID datang daripada URL dan boleh menunjuk ke mana-mana.
     *
     * @return array<int, array{product_id: int, kuantiti: int, kos_seunit: string}>
     */
    private function barisAwal(Request $request): array
    {
        $diminta = $request->input('produk');

        if (! is_array($diminta) || $diminta === []) {
            return [];
        }

        $sah = Product::whereIn('id', array_keys($diminta))->pluck('id')->all();

        $baris = [];

        foreach ($sah as $id) {
            $kuantiti = (int) ($diminta[$id] ?? 0);

            if ($kuantiti > 0) {
                // Kos dibiarkan kosong: pengawal akan mengambil harga kos
                // produk semasa menyimpan, dan pengguna boleh menaipnya semula
                // kalau pembekal memberi harga lain.
                $baris[] = ['product_id' => $id, 'kuantiti' => $kuantiti, 'kos_seunit' => ''];
            }
        }

        return $baris;
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->disahkan($request);
        $baris = $this->barisTerisi($data['baris']);

        if ($baris === []) {
            return back()->withInput()->with('ralat', __('wky.flash.po_perlu_baris'));
        }

        $pesanan = DB::transaction(function () use ($data, $baris, $request) {
            $pesanan = PurchaseOrder::create([
                'kod' => $this->kodSeterusnya(),
                'status' => 'draf',
                'supplier_id' => $data['supplier_id'] ?? null,
                'dipohon_oleh' => $request->user()?->id,
                'tarikh_diperlukan' => $data['tarikh_diperlukan'] ?? null,
                'catatan' => $data['catatan'] ?? null,
            ]);

            $this->tulisBaris($pesanan, $baris);

            return $pesanan;
        });

        return redirect()->route('purchase-orders.show', $pesanan)
            ->with('status', __('wky.flash.po_dicipta', ['kod' => $pesanan->kod]));
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['items.product', 'supplier', 'pemohon', 'pemutus']);

        return view('purchase-orders.show', [
            'pesanan' => $purchaseOrder,
            'locations' => Location::aktif()->orderByDesc('lalai')->orderBy('nama')->get(),
            'lokasiLalai' => Location::lalai()?->id,
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        abort_unless($purchaseOrder->bolehDisunting(), 403);

        $purchaseOrder->load('items');

        return view('purchase-orders.form', [
            'pesanan' => $purchaseOrder,
            'suppliers' => Supplier::orderBy('nama')->get(),
            'products' => Product::where('aktif', true)->orderBy('nama')->get(),
            'awal' => [],
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        // Hanya draf yang boleh disunting. Selepas dihantar, isi PO ialah apa
        // yang orang lain baca dan luluskan — menyuntingnya di belakang mereka
        // menjadikan kelulusan itu tidak bermakna.
        abort_unless($purchaseOrder->bolehDisunting(), 403);

        $data = $this->disahkan($request);
        $baris = $this->barisTerisi($data['baris']);

        if ($baris === []) {
            return back()->withInput()->with('ralat', __('wky.flash.po_perlu_baris'));
        }

        DB::transaction(function () use ($data, $baris, $purchaseOrder) {
            $purchaseOrder->update([
                'supplier_id' => $data['supplier_id'] ?? null,
                'tarikh_diperlukan' => $data['tarikh_diperlukan'] ?? null,
                'catatan' => $data['catatan'] ?? null,
            ]);

            // Baris ditulis semula sepenuhnya. Draf belum menyentuh stok, jadi
            // tiada sejarah yang hilang — dan memadankan baris lama dengan
            // baris baharu satu demi satu hanya menambah cara untuk tersalah.
            $purchaseOrder->items()->delete();
            $this->tulisBaris($purchaseOrder, $baris);
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('status', __('wky.flash.po_dikemas_kini', ['kod' => $purchaseOrder->kod]));
    }

    /** Menghantar permohonan untuk kelulusan. */
    public function submit(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->bolehKe('menunggu'), 403);

        $purchaseOrder->update(['status' => 'menunggu']);

        return back()->with('status', __('wky.flash.po_dihantar', ['kod' => $purchaseOrder->kod]));
    }

    /**
     * Meluluskan atau menolak permohonan.
     *
     * Laluan ini dijaga middleware admin, jadi staf boleh memohon tetapi tidak
     * boleh meluluskan permohonannya sendiri.
     */
    public function decide(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $data = $request->validate([
            'keputusan' => ['required', 'in:diluluskan,ditolak'],
            'sebab_tolak' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($purchaseOrder->bolehKe($data['keputusan']), 403);

        $purchaseOrder->update([
            'status' => $data['keputusan'],
            'diputuskan_oleh' => $request->user()?->id,
            'diputuskan_pada' => now(),
            'sebab_tolak' => $data['keputusan'] === 'ditolak' ? ($data['sebab_tolak'] ?? null) : null,
        ]);

        return back()->with('status', __(
            $data['keputusan'] === 'diluluskan' ? 'wky.flash.po_diluluskan' : 'wky.flash.po_ditolak',
            ['kod' => $purchaseOrder->kod],
        ));
    }

    /**
     * Menerima barang, penuh atau separa.
     *
     * Setiap baris yang diterima menjana satu pergerakan stok masuk dengan
     * sebab "pembelian", membawa kos yang diluluskan pada baris PO itu. Baki
     * gudang dan lot penerimaan dikemas kini melalui perkhidmatan yang sama
     * seperti aliran stok yang lain, supaya tiada satu aliran pun terlepas
     * semakan bakinya sendiri.
     */
    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->bolehTerima(), 403);

        $purchaseOrder->load('items');

        $data = $request->validate([
            'location_id' => ['nullable', Rule::exists('locations', 'id')
                ->where('workspace_id', $request->user()->workspace_id)
                ->where('aktif', true)],
            'terima' => ['required', 'array'],
            'terima.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $lokasi = (int) ($data['location_id'] ?? Location::lalai()?->id);
        $diterima = 0;

        try {
            DB::transaction(function () use ($purchaseOrder, $data, $lokasi, $request, &$diterima) {
                foreach ($purchaseOrder->items as $item) {
                    $kuantiti = (int) ($data['terima'][$item->id] ?? 0);

                    if ($kuantiti <= 0) {
                        continue;
                    }

                    // Menerima lebih daripada yang dipesan ialah percanggahan
                    // dengan dokumen yang diluluskan, bukan kelonggaran yang
                    // memudahkan. Lebihan sebenar direkod sebagai stok masuk
                    // biasa, di luar PO ini.
                    if ($kuantiti > $item->bakiTerima()) {
                        throw new RuntimeException(__('wky.flash.po_lebih_terima', [
                            'produk' => $item->product?->nama ?? '',
                            'baki' => $item->bakiTerima(),
                        ]));
                    }

                    $product = Product::lockForUpdate()->findOrFail($item->product_id);

                    $sebelum = $product->stok;
                    $selepas = $sebelum + $kuantiti;

                    BakiLokasi::laraskan($product, $lokasi, $kuantiti);
                    $product->update(['stok' => $selepas]);

                    $kos = $item->kos_seunit !== null ? (float) $item->kos_seunit : null;

                    StockMovement::create([
                        'product_id' => $product->id,
                        'product_batch_id' => LotPenerimaan::serap($product, $purchaseOrder->kod, $kuantiti, $kos)?->id,
                        'location_id' => $lokasi,
                        'user_id' => $request->user()?->id,
                        'jenis' => 'masuk',
                        'sebab' => 'pembelian',
                        'kuantiti' => $kuantiti,
                        'kos_seunit' => $kos,
                        'stok_sebelum' => $sebelum,
                        'stok_selepas' => $selepas,
                        'rujukan' => $purchaseOrder->kod,
                        'catatan' => __('wky.po.catatan_pergerakan', ['kod' => $purchaseOrder->kod]),
                    ]);

                    $item->increment('kuantiti_diterima', $kuantiti);
                    $diterima++;
                }

                // Status hanya bergerak ke selesai apabila setiap baris sudah
                // penuh. Dibaca semula supaya kiraan tidak bergantung pada
                // salinan dalam ingatan yang baru sahaja ditambah.
                if ($purchaseOrder->load('items')->penerimaanSelesai()) {
                    $purchaseOrder->update(['status' => 'selesai']);
                }
            });
        } catch (RuntimeException $e) {
            return back()->with('ralat', $e->getMessage());
        }

        if ($diterima === 0) {
            return back()->with('ralat', __('wky.flash.po_tiada_terima'));
        }

        return back()->with('status', __(
            $purchaseOrder->fresh()->status === 'selesai' ? 'wky.flash.po_selesai' : 'wky.flash.po_terima_separa',
            ['kod' => $purchaseOrder->kod],
        ));
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->bolehKe('dibatalkan'), 403);

        // PO yang sudah menerima sebahagian barang tidak boleh dibatalkan:
        // stok itu sudah masuk ke dalam gudang, dan membatalkan dokumennya
        // meninggalkan pergerakan stok yang merujuk kepada sesuatu yang
        // sepatutnya tidak pernah berlaku.
        if ($purchaseOrder->load('items')->jumlahDiterima() > 0) {
            return back()->with('ralat', __('wky.flash.po_sudah_terima'));
        }

        $purchaseOrder->update(['status' => 'dibatalkan']);

        return back()->with('status', __('wky.flash.po_dibatalkan', ['kod' => $purchaseOrder->kod]));
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        // Hanya draf boleh dipadam. Selepas dihantar, PO ialah sebahagian
        // daripada jejak keputusan — termasuk yang ditolak, kerana "kami pernah
        // memohon dan ia ditolak" ialah maklumat.
        abort_unless($purchaseOrder->status === 'draf', 403);

        $kod = $purchaseOrder->kod;
        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')
            ->with('status', __('wky.flash.po_dipadam', ['kod' => $kod]));
    }

    /** @return array<string, mixed> */
    private function disahkan(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')
                ->where('workspace_id', $request->user()->workspace_id)],
            'tarikh_diperlukan' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
            'baris' => ['required', 'array'],
            'baris.*.product_id' => ['nullable', Rule::exists('products', 'id')
                ->where('workspace_id', $request->user()->workspace_id)],
            'baris.*.kuantiti' => ['nullable', 'integer', 'min:1'],
            'baris.*.kos_seunit' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    /**
     * Baris yang benar-benar diisi, dengan produk berulang digabungkan.
     *
     * Borang membenarkan baris ditambah sesuka hati, jadi produk yang sama
     * boleh dipilih dua kali — dan lajur unik pada (purchase_order_id,
     * product_id) akan menolak keseluruhan permohonan kalau itu berlaku.
     * Kos baris terakhir yang menang, kerana itulah yang pengguna taip terakhir.
     *
     * @param  array<int, array<string, mixed>>  $baris
     * @return array<int, array{product_id: int, kuantiti: int, kos_seunit: float|null}>
     */
    private function barisTerisi(array $baris): array
    {
        $digabung = [];

        foreach ($baris as $satu) {
            $produk = (int) ($satu['product_id'] ?? 0);
            $kuantiti = (int) ($satu['kuantiti'] ?? 0);

            if ($produk === 0 || $kuantiti <= 0) {
                continue;
            }

            $kos = ($satu['kos_seunit'] ?? null) === null || $satu['kos_seunit'] === ''
                ? ($digabung[$produk]['kos_seunit'] ?? null)
                : (float) $satu['kos_seunit'];

            $digabung[$produk] = [
                'product_id' => $produk,
                'kuantiti' => ($digabung[$produk]['kuantiti'] ?? 0) + $kuantiti,
                'kos_seunit' => $kos,
            ];
        }

        return array_values($digabung);
    }

    /**
     * @param  array<int, array{product_id: int, kuantiti: int, kos_seunit: float|null}>  $baris
     */
    private function tulisBaris(PurchaseOrder $pesanan, array $baris): void
    {
        foreach ($baris as $satu) {
            // Kos yang dibiarkan kosong mengambil harga kos produk, sama seperti
            // borang stok masuk. Produk yang harga kosnya belum ditetapkan
            // meninggalkan kos sebagai "tidak diketahui" dan bukan sifar.
            $kos = $satu['kos_seunit'];

            if ($kos === null) {
                $harga = (float) (Product::find($satu['product_id'])?->harga_kos ?? 0);
                $kos = $harga > 0 ? $harga : null;
            }

            $pesanan->items()->create([
                'product_id' => $satu['product_id'],
                'kuantiti' => $satu['kuantiti'],
                'kos_seunit' => $kos,
            ]);
        }
    }

    private function kodSeterusnya(): string
    {
        $tahun = now()->format('Y');
        $bil = PurchaseOrder::where('kod', 'like', "PO-{$tahun}-%")->count() + 1;

        return sprintf('PO-%s-%03d', $tahun, $bil);
    }
}
