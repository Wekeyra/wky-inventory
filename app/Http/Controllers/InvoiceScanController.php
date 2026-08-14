<?php

namespace App\Http\Controllers;

use App\Models\InvoiceScan;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\Stok\BakiLokasi;
use App\Services\Stok\LotPenerimaan;
use App\Services\Invoice\ExtractedInvoice;
use App\Services\Invoice\ExtractedLine;
use App\Services\Invoice\InvoiceExtractionException;
use App\Services\Invoice\InvoiceExtractor;
use App\Services\Invoice\ProductMatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceScanController extends Controller
{
    public function index(): View
    {
        return view('invoice-scans.index', [
            'imbasan' => InvoiceScan::query()
                ->with('pembuka')
                ->withCount('items')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('invoice-scans.create', [
            'adaKunci' => filled(config('anthropic.api_key')),
            'saizMaksKb' => (int) config('anthropic.saiz_maks_kb'),
        ]);
    }

    public function store(Request $request, InvoiceExtractor $extractor, ProductMatcher $matcher): RedirectResponse
    {
        $data = $request->validate([
            'invois' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:'.(int) config('anthropic.saiz_maks_kb')],
            'catatan' => ['nullable', 'string'],
            'tindakan' => ['nullable', 'in:baca,simpan'],
        ]);

        $fail = $data['invois'];
        $simpanSahaja = ($data['tindakan'] ?? 'baca') === 'simpan';

        // Gambar disimpan dahulu supaya ia tidak hilang jika bacaan AI gagal;
        // imbasan yang tersimpan boleh dicuba baca semula bila-bila masa.
        $imbasan = InvoiceScan::create([
            'kod' => $this->kodSeterusnya(),
            'status' => 'draf',
            'laluan_fail' => $fail->store('invois', 'local'),
            'nama_fail_asal' => $fail->getClientOriginalName(),
            'jenis_mime' => $fail->getMimeType(),
            'dibuka_oleh' => $request->user()?->id,
            'catatan' => $data['catatan'] ?? null,
        ]);

        if ($simpanSahaja) {
            return redirect()->route('invoice-scans.show', $imbasan)
                ->with('status', __('wky.flash.imbas_disimpan_sahaja', ['kod' => $imbasan->kod]));
        }

        return $this->bacaImbasan($imbasan, $extractor, $matcher);
    }

    /** Membaca gambar yang sudah tersimpan dengan AI, untuk imbasan Simpan Sahaja. */
    public function read(InvoiceScan $invoiceScan, InvoiceExtractor $extractor, ProductMatcher $matcher): RedirectResponse
    {
        $this->pastikanDraf($invoiceScan);

        if (! $invoiceScan->belumDibaca()) {
            return back()->with('ralat', __('wky.imbas.ralat_sudah_dibaca'));
        }

        return $this->bacaImbasan($invoiceScan, $extractor, $matcher);
    }

    /**
     * Menghantar fail imbasan kepada AI dan menyimpan baris yang dibacanya.
     *
     * Kegagalan mengembalikan pengguna dengan mesej dan membiarkan imbasan
     * kekal belum dibaca, supaya ia boleh dicuba semula tanpa memuat naik
     * gambar yang sama sekali lagi.
     */
    private function bacaImbasan(InvoiceScan $imbasan, InvoiceExtractor $extractor, ProductMatcher $matcher): RedirectResponse
    {
        if (blank(config('anthropic.api_key'))) {
            return redirect()->route('invoice-scans.show', $imbasan)
                ->with('ralat', __('wky.imbas.ralat_tiada_kunci'));
        }

        // Panggilan penglihatan boleh mengambil masa lebih lama daripada had
        // pelaksanaan PHP yang lalai, jadi had itu dinaikkan untuk permintaan ini.
        @set_time_limit((int) config('anthropic.timeout') + 30);

        try {
            $hasil = $extractor->extract(
                Storage::disk('local')->get($imbasan->laluan_fail),
                $imbasan->jenis_mime,
            );
        } catch (InvoiceExtractionException $e) {
            return redirect()->route('invoice-scans.show', $imbasan)->with('ralat', $e->getMessage());
        }

        if ($hasil->barang === []) {
            return redirect()->route('invoice-scans.show', $imbasan)
                ->with('ralat', __('wky.imbas.ralat_tiada_baris'));
        }

        DB::transaction(function () use ($imbasan, $hasil, $matcher) {
            $imbasan->update([
                'no_invois' => $hasil->noInvois,
                'tarikh_invois' => $hasil->tarikhInvois,
                'nama_pembekal' => $hasil->namaPembekal,
                'supplier_id' => $this->tekaPembekal($hasil),
                'dibaca_pada' => now(),
            ]);

            foreach ($hasil->barang as $baris) {
                $padanan = $matcher->match($baris);

                // Baris tanpa padanan bermakna produk itu belum wujud, bukan
                // pengguna terlepas pandang. Ia dicipta terus di sini supaya
                // imbasan sampai ke skrin semakan dalam keadaan sedia direkod
                // dan pengguna tidak perlu berhenti mendaftarkan produk dahulu.
                if ($padanan['product'] === null) {
                    $padanan = ['product' => $this->ciptaProduk($baris, $matcher), 'kaedah' => 'auto'];
                }

                $imbasan->items()->create([
                    'product_id' => $padanan['product']->id,
                    'sku_invois' => $baris->sku,
                    'nama_invois' => $baris->nama,
                    'kuantiti' => $baris->kuantiti,
                    'harga_unit' => $baris->hargaUnit,
                    'kaedah_padanan' => $padanan['kaedah'],
                ]);
            }
        });

        $baharu = $imbasan->items()->where('kaedah_padanan', 'auto')->count();

        return redirect()->route('invoice-scans.show', $imbasan)->with('status', __(
            $baharu > 0 ? 'wky.flash.imbas_dibaca_auto' : 'wky.flash.imbas_dibaca',
            [
                'bil' => $imbasan->items()->count(),
                'tiada' => $imbasan->items()->whereNull('product_id')->count(),
                'baharu' => $baharu,
            ],
        ));
    }

    public function show(InvoiceScan $invoiceScan): View
    {
        $invoiceScan->load(['items.product', 'pembuka', 'pengesah', 'supplier', 'purchaseOrder']);

        return view('invoice-scans.show', [
            'imbasan' => $invoiceScan,
            'products' => Product::where('aktif', true)->orderBy('nama')->get(),
            'suppliers' => Supplier::orderBy('nama')->get(),
            // Hanya pesanan yang diluluskan masih menunggu barang. Pesanan yang
            // sudah dipautkan kekal dalam senarai walaupun statusnya berubah,
            // supaya paparan tidak kehilangan pilihannya sendiri.
            'pesanan' => PurchaseOrder::query()
                ->where(fn ($q) => $q->where('status', 'diluluskan')
                    ->orWhere('id', $invoiceScan->purchase_order_id))
                ->with('supplier')
                ->latest()
                ->get(),
        ]);
    }

    /** Menstrim fail invois asal supaya pengguna boleh membandingkannya dengan padanan. */
    public function file(InvoiceScan $invoiceScan): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($invoiceScan->laluan_fail), 404);

        return Storage::disk('local')->response(
            $invoiceScan->laluan_fail,
            $invoiceScan->nama_fail_asal,
            ['Content-Type' => $invoiceScan->jenis_mime],
        );
    }

    /** Menyimpan pembetulan pengguna tanpa menyentuh stok. */
    public function update(Request $request, InvoiceScan $invoiceScan): RedirectResponse
    {
        $this->pastikanDraf($invoiceScan);

        $data = $request->validate([
            'no_invois' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')
                ->where('workspace_id', $request->user()->workspace_id)],
            // Hanya pesanan yang diluluskan boleh dipautkan. Pesanan draf belum
            // dipersetujui sesiapa, dan pesanan yang selesai sudah tiada baki
            // untuk dipenuhi oleh invois ini.
            'purchase_order_id' => ['nullable', Rule::exists('purchase_orders', 'id')
                ->where('workspace_id', $request->user()->workspace_id)
                ->where('status', 'diluluskan')],
            'catatan' => ['nullable', 'string'],
            'baris' => ['array'],
            'baris.*.product_id' => ['nullable', Rule::exists('products', 'id')
                ->where('workspace_id', $request->user()->workspace_id)],
            'baris.*.kuantiti' => ['nullable', 'integer', 'min:1'],
            'baris.*.dilangkau' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($invoiceScan, $data, $request) {
            $invoiceScan->update([
                'no_invois' => $data['no_invois'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'catatan' => $data['catatan'] ?? null,
            ]);

            foreach ($invoiceScan->items as $item) {
                if (! array_key_exists($item->id, $data['baris'] ?? [])) {
                    continue;
                }

                $baris = $data['baris'][$item->id];
                $produkBaharu = $baris['product_id'] ?? null;

                $item->update([
                    'product_id' => $produkBaharu,
                    'kuantiti' => $baris['kuantiti'] ?? $item->kuantiti,
                    'dilangkau' => (bool) ($baris['dilangkau'] ?? false),
                    // Padanan yang ditukar oleh pengguna ditanda 'manual' supaya
                    // jelas mana satu datang daripada AI dan mana satu daripada orang.
                    'kaedah_padanan' => $this->kaedahPadanan($item->getOriginal('product_id'), $produkBaharu, $item->kaedah_padanan),
                ]);
            }
        });

        return back()->with('status', __('wky.flash.imbas_disimpan'));
    }

    /** Mengesahkan imbasan: setiap baris yang dipadankan menjana pergerakan stok masuk. */
    public function confirm(Request $request, InvoiceScan $invoiceScan): RedirectResponse
    {
        $this->pastikanDraf($invoiceScan);

        if ($invoiceScan->belumDibaca()) {
            return back()->with('ralat', __('wky.imbas.ralat_belum_dibaca'));
        }

        $bolehDiproses = $invoiceScan->items->filter->bolehDiproses();

        if ($bolehDiproses->isEmpty()) {
            return back()->with('ralat', __('wky.imbas.ralat_tiada_padanan'));
        }

        // Invois tidak menyebut gudang mana yang menerima barang, jadi ia masuk
        // ke gudang lalai. Pindahkan kemudian jika ia sepatutnya di tempat lain
        // — itu pergerakan yang berhak mendapat rekodnya sendiri.
        $lokasi = Location::lalai();
        $direkod = 0;

        DB::transaction(function () use ($invoiceScan, $bolehDiproses, $request, $lokasi, &$direkod) {
            foreach ($bolehDiproses as $item) {
                // Baki dibaca semula dengan kunci di sini kerana stok mungkin
                // berubah antara imbasan dibuat dan disahkan.
                $product = Product::lockForUpdate()->find($item->product_id);

                if ($product === null) {
                    continue;
                }

                $sebelum = $product->stok;
                $selepas = $sebelum + $item->kuantiti;

                if ($lokasi !== null) {
                    BakiLokasi::laraskan($product, $lokasi->id, $item->kuantiti);
                }

                $product->update(['stok' => $selepas]);

                // Harga unit yang dibaca AI daripada invois ialah kos sebenar
                // penerimaan ini, jadi ia dibekukan pada pergerakan dan pada lot
                // penerimaannya. Baris tanpa harga jatuh kepada harga kos produk,
                // dan produk yang harga kosnya belum ditetapkan langsung
                // meninggalkan kos sebagai "tidak direkod" — bukan sifar.
                $kos = $item->harga_unit !== null
                    ? (float) $item->harga_unit
                    : ((float) $product->harga_kos > 0 ? (float) $product->harga_kos : null);

                StockMovement::create([
                    'product_id' => $product->id,
                    'product_batch_id' => LotPenerimaan::serap($product, $invoiceScan->rujukanStok(), $item->kuantiti, $kos)?->id,
                    'location_id' => $lokasi?->id,
                    'user_id' => $request->user()?->id,
                    'jenis' => 'masuk',
                    'sebab' => 'pembelian',
                    'kuantiti' => $item->kuantiti,
                    'kos_seunit' => $kos,
                    'stok_sebelum' => $sebelum,
                    'stok_selepas' => $selepas,
                    'rujukan' => $invoiceScan->rujukanStok(),
                    'catatan' => __('wky.imbas.catatan_pergerakan', ['kod' => $invoiceScan->kod]),
                ]);

                $direkod++;
            }

            $this->majukanPesanan($invoiceScan, $bolehDiproses);

            $invoiceScan->update([
                'status' => 'selesai',
                'disahkan_oleh' => $request->user()?->id,
                'disahkan_pada' => now(),
            ]);
        });

        $pesanan = $invoiceScan->fresh()->purchaseOrder;

        return redirect()->route('invoice-scans.show', $invoiceScan)->with('status', $pesanan === null
            ? __('wky.flash.imbas_disahkan', ['kod' => $invoiceScan->kod, 'bil' => $direkod])
            : __('wky.flash.imbas_disahkan_po', [
                'kod' => $invoiceScan->kod,
                'bil' => $direkod,
                'po' => $pesanan->kod,
                'status' => $pesanan->labelStatus(),
            ]));
    }

    /**
     * Memadam imbasan berserta gambarnya.
     *
     * Hanya draf boleh dipadam. Imbasan yang telah disahkan sudah menjana
     * pergerakan stok yang merujuk kodnya, dan membuangnya akan meninggalkan
     * pergerakan yang menunjuk kepada imbasan yang tidak lagi wujud —
     * pastikanDraf() menahannya di sini, bukan hanya menyembunyikan butang.
     */
    public function destroy(InvoiceScan $invoiceScan): RedirectResponse
    {
        $this->pastikanDraf($invoiceScan);

        // Kod dibaca dahulu kerana ia diperlukan untuk mesej selepas rekod hilang.
        $kod = $invoiceScan->kod;

        // Fail dibuang sebelum rekod kerana storan tidak boleh digulung semula:
        // rekod tanpa fail masih boleh dipadam, tetapi fail tanpa rekod menjadi
        // sampah yang tiada sesiapa boleh capai. Baris barangnya dibuang melalui
        // cascade pada kunci asing invoice_scan_items.
        Storage::disk('local')->delete($invoiceScan->laluan_fail);

        $invoiceScan->delete();

        return redirect()->route('invoice-scans.index')
            ->with('status', __('wky.flash.imbas_dipadam', ['kod' => $kod]));
    }

    /**
     * Lot penerimaan bagi produk yang dijejak batchnya.
     *
     * Invois tidak membawa nombor batch, jadi satu penghantaran dianggap satu
     * lot dan dinamakan mengikut rujukan invois itu. Tanpa ini, baki batch akan
     * tertinggal di belakang baki produk setiap kali stok masuk melalui
     * imbasan — dan angka batch yang tidak boleh dipercayai lebih buruk
     * daripada tiada batch langsung.
     *
     * Tarikh luputnya dibiarkan kosong kerana ia memang tidak diketahui di
     * sini; ia diisi pada halaman produk selepas kotak sebenar diperiksa.
     */
    // Logik lot penerimaan berpindah ke Services\Stok\LotPenerimaan, kerana
    // penerimaan Purchase Order menghadapi masalah yang sama persis.

    /**
     * Memajukan kuantiti diterima pada pesanan belian yang dipautkan.
     *
     * Stok sudah pun direkod oleh pemanggil; ini hanya memberitahu pesanan
     * bahawa barangnya sudah sampai. Tanpa langkah ini, pesanan kekal
     * "diluluskan" selama-lamanya walaupun invoisnya sudah dibayar dan
     * barangnya sudah di rak.
     *
     * Invois boleh membawa lebih daripada baki pesanan — pembekal menghantar
     * lebih, atau invois yang sama menutup dua pesanan. Lebihan itu **tetap
     * masuk ke stok**, kerana barang itu memang sampai; yang dihadkan hanyalah
     * berapa banyak daripadanya dikira terhadap pesanan ini. Menolak
     * keseluruhan pengesahan kerana pesanan tidak sepadan bermakna stok sebenar
     * tidak boleh direkod, dan itu lebih memudaratkan daripada satu pesanan
     * yang tidak seimbang.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\InvoiceScanItem>  $baris
     */
    private function majukanPesanan(InvoiceScan $imbasan, $baris): void
    {
        $pesanan = $imbasan->purchaseOrder;

        if ($pesanan === null || ! $pesanan->bolehTerima()) {
            return;
        }

        $pesanan->load('items');

        // Kuantiti invois dijumlahkan mengikut produk dahulu: satu invois boleh
        // menyenaraikan produk yang sama pada dua baris, dan pesanan hanya
        // mempunyai satu baris bagi setiap produk.
        $mengikutProduk = $baris->groupBy('product_id')->map(fn ($kumpulan) => (int) $kumpulan->sum('kuantiti'));

        foreach ($pesanan->items as $item) {
            $sampai = (int) ($mengikutProduk[$item->product_id] ?? 0);

            if ($sampai <= 0) {
                continue;
            }

            $item->increment('kuantiti_diterima', min($sampai, $item->bakiTerima()));
        }

        if ($pesanan->load('items')->penerimaanSelesai()) {
            $pesanan->update(['status' => 'selesai']);
        }
    }

    /**
     * Mencipta produk daripada satu baris invois yang tiada padanan.
     *
     * Invois tidak membawa harga jual mahupun paras stok minimum, jadi kedua-dua
     * itu bermula pada 0 dan pengguna membetulkannya di halaman Produk. Stok
     * pula sengaja tidak ditetapkan di sini: ia hanya bergerak melalui
     * pengesahan imbasan, supaya jejak audit kekal utuh seperti mana-mana
     * kemasukan stok yang lain.
     */
    private function ciptaProduk(ExtractedLine $baris, ProductMatcher $matcher): Product
    {
        $product = Product::create([
            'sku' => $this->skuProduk($baris),
            'nama' => $baris->nama,
            'unit' => 'unit',
            'harga_kos' => $baris->hargaUnit ?? 0,
            'harga_jual' => 0,
            'stok_minimum' => 0,
            'aktif' => true,
        ]);

        $matcher->daftar($product);

        return $product;
    }

    /**
     * Kod pembekal dijadikan SKU apabila ada, kerana itulah yang menjadikan
     * invois berikutnya daripada pembekal yang sama padan dengan sendirinya.
     *
     * Baris tanpa kod mendapat kod jana supaya medan SKU yang wajib itu tetap
     * terisi; padanan seterusnya bagi baris begitu bergantung pada nama.
     */
    private function skuProduk(ExtractedLine $baris): string
    {
        $sku = mb_substr(trim((string) $baris->sku), 0, 50);

        // Kod yang sudah dipakai tidak boleh diguna semula: keunikan SKU
        // berskop ruang kerja, dan pelanggarannya akan mematikan imbasan di
        // tengah jalan. Padanan normal sepatutnya sudah menangkap kes ini,
        // tetapi pemotongan 50 aksara di atas boleh menghasilkan pertembungan.
        if ($sku !== '' && ! Product::where('sku', $sku)->exists()) {
            return $sku;
        }

        $bil = Product::where('sku', 'like', 'AUTO-%')->count();

        do {
            $bil++;
            $jana = sprintf('AUTO-%04d', $bil);
        } while (Product::where('sku', $jana)->exists());

        return $jana;
    }

    private function kaedahPadanan(?int $asal, ?int $baharu, string $semasa): string
    {
        if ($baharu === null) {
            return 'tiada';
        }

        return $asal === $baharu ? $semasa : 'manual';
    }

    private function tekaPembekal(ExtractedInvoice $hasil): ?int
    {
        if ($hasil->namaPembekal === null) {
            return null;
        }

        return Supplier::whereRaw('LOWER(nama) = ?', [mb_strtolower($hasil->namaPembekal)])->value('id');
    }

    private function pastikanDraf(InvoiceScan $invoiceScan): void
    {
        if (! $invoiceScan->isDraf()) {
            throw ValidationException::withMessages([
                'status' => __('wky.flash.imbas_terkunci', [
                    'kod' => $invoiceScan->kod,
                    'status' => mb_strtolower($invoiceScan->labelStatus()),
                ]),
            ]);
        }
    }

    private function kodSeterusnya(): string
    {
        $tahun = now()->format('Y');
        $bil = InvoiceScan::where('kod', 'like', "SCAN-{$tahun}-%")->count() + 1;

        return sprintf('SCAN-%s-%03d', $tahun, $bil);
    }
}
