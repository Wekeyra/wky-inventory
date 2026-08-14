<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InvoiceScanItem;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    /** Saiz maksimum gambar produk dalam kilobait. */
    private const SAIZ_GAMBAR_KB = 4096;

    public function index(Request $request): View
    {
        $cari = $request->string('cari')->toString();

        $products = Product::query()
            ->with(['category', 'supplier'])
            // Barcode disertakan dalam carian supaya kod yang diimbas terus ke
            // dalam medan ini menemui produknya tanpa halaman berasingan.
            ->when($cari, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('sku', 'like', "%{$cari}%")
                ->orWhere('barcode', 'like', "%{$cari}%")))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->boolean('stok_rendah'), fn ($q) => $q->stokRendah())
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::orderBy('nama')->get(),
            'cari' => $cari,
        ]);
    }

    /**
     * Borang produk baharu, yang boleh dimulakan daripada satu baris imbasan
     * invois yang tiada padanan.
     *
     * Nilai awal dibaca daripada baris itu sendiri dan bukan daripada parameter
     * URL, supaya apa yang terisi memang apa yang AI baca dan bukan apa yang
     * disuap ke dalam pautan.
     */
    public function create(Request $request): View
    {
        $baris = $this->barisImbasan($request->integer('baris_imbasan'));

        return view('products.form', [
            'product' => new Product([
                'sku' => $baris?->sku_invois,
                'nama' => $baris?->nama_invois,
                'harga_kos' => $baris?->harga_unit,
                'unit' => 'unit',
                'aktif' => true,
            ]),
            'barisImbasan' => $baris,
            'categories' => Category::orderBy('nama')->get(),
            'suppliers' => Supplier::orderBy('nama')->get(),
            'kembali' => $this->kembali($request),
        ]);
    }

    /**
     * Ke mana borang ini patut pulang selepas disimpan.
     *
     * Kata kunci, bukan URL. Menerima URL penuh daripada permintaan bermakna
     * sesiapa boleh menghantar pautan yang mengalihkan pengguna ke tapak lain
     * selepas dia menekan Simpan.
     *
     * Kedua-dua destinasi ini ialah tempat pengguna berhenti seketika untuk
     * mencipta produk yang hilang daripada senarai pilihan — bukan tempat dia
     * datang untuk menguruskan produk.
     */
    private function kembali(Request $request): ?string
    {
        return in_array($request->input('kembali'), ['dashboard', 'stok'], true)
            ? $request->input('kembali')
            : null;
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['laluan_gambar'] = $this->simpanGambar($request);

        $product = Product::create($data);

        $baris = $this->barisImbasan($request->integer('baris_imbasan'));

        // Produk yang baharu dicipta tidak akan dipadankan sendiri: padanan
        // berlaku sekali sahaja semasa AI membaca invois. Tanpa langkah ini
        // pengguna terpaksa memilih semula produk yang baru sahaja dia cipta.
        if ($baris !== null) {
            $baris->update(['product_id' => $product->id, 'kaedah_padanan' => 'manual']);

            return redirect()->route('invoice-scans.show', $baris->invoice_scan_id)
                ->with('status', __('wky.flash.produk_tambah_padan', ['nama' => $product->nama]));
        }

        // Produk yang dicipta daripada borang stok pulang ke situ dengan
        // produk baharu itu sudah terpilih, supaya pengguna tidak perlu
        // mencarinya semula dalam senarai yang baru sahaja dia tambah.
        $kembali = $this->kembali($request);

        if ($kembali === 'stok') {
            return redirect()->route('stock.create', ['product_id' => $product->id])
                ->with('status', __('wky.flash.produk_tambah'));
        }

        if ($kembali === 'dashboard') {
            return redirect()->route('dashboard')->with('status', __('wky.flash.produk_tambah'));
        }

        return redirect()->route('products.index')->with('status', __('wky.flash.produk_tambah'));
    }

    public function show(Product $product): View
    {
        $product->load('category', 'supplier', 'balances');

        return view('products.show', [
            'product' => $product,
            'movements' => $product->movements()->with(['user', 'batch', 'location', 'tujuan'])->latest()->paginate(15),
            // Baki setiap gudang, termasuk gudang yang kosong buat masa ini —
            // "tiada di cawangan Ampang" ialah jawapan yang berguna, bukan
            // baris yang patut disembunyikan.
            'balances' => Location::aktif()
                ->orderByDesc('lalai')
                ->orderBy('nama')
                ->get()
                ->map(fn (Location $lokasi) => [
                    'lokasi' => $lokasi,
                    'baki' => $product->balances->firstWhere('location_id', $lokasi->id),
                ]),
            // Batch kosong disembunyikan: senarai lot yang sudah habis hanya
            // memanjangkan jadual tanpa memberitahu apa-apa tentang baki semasa.
            'batches' => $product->jejak_batch
                ? $product->batches()->adaBaki()->orderByRaw('tarikh_luput is null, tarikh_luput')->get()
                : collect(),
        ]);
    }

    public function edit(Product $product): View
    {
        // Menyunting produk sedia ada sentiasa bermula daripada senarai produk,
        // jadi tiada tempat lain untuk pulang.
        return view('products.form', [
            'product' => $product,
            'barisImbasan' => null,
            'categories' => Category::orderBy('nama')->get(),
            'suppliers' => Supplier::orderBy('nama')->get(),
            'kembali' => null,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $lama = $product->laluan_gambar;
        $baharu = $this->simpanGambar($request);

        if ($baharu !== null) {
            $data['laluan_gambar'] = $baharu;
        } elseif ($request->boolean('buang_gambar')) {
            $data['laluan_gambar'] = null;
        }

        $product->update($data);

        // Gambar lama dibuang selepas rekod dikemas kini, supaya kegagalan
        // pengesahan tidak meninggalkan produk yang gambarnya sudah lesap.
        if ($lama !== null && $product->laluan_gambar !== $lama) {
            Storage::disk('local')->delete($lama);
        }

        return redirect()->route('products.index')->with('status', __('wky.flash.produk_kemas_kini'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $gambar = $product->laluan_gambar;

        $product->delete();

        if ($gambar !== null) {
            Storage::disk('local')->delete($gambar);
        }

        return redirect()->route('products.index')->with('status', __('wky.flash.produk_padam'));
    }

    /**
     * Menstrim gambar produk.
     *
     * Gambar disimpan pada cakera peribadi dan bukan dalam public/, jadi ia
     * melalui pengikatan model yang berskop ruang kerja — sama seperti fail
     * invois. Produk syarikat lain memulangkan 404 dan bukan gambar.
     */
    public function gambar(Product $product): StreamedResponse
    {
        abort_if($product->laluan_gambar === null, 404);
        abort_unless(Storage::disk('local')->exists($product->laluan_gambar), 404);

        return Storage::disk('local')->response($product->laluan_gambar, null, [
            'Content-Type' => Storage::disk('local')->mimeType($product->laluan_gambar),
            // Gambar produk jarang berubah dan setiap baris senarai memintanya,
            // jadi ia dicache pada pelayar. URL kekal sama apabila gambar
            // ditukar, jadi cache sengaja dipendekkan kepada sejam.
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Baris imbasan yang sah untuk dipautkan dengan produk baharu.
     *
     * Skop global pada InvoiceScan menapis mengikut ruang kerja, jadi whereHas
     * di sini menyebabkan baris milik syarikat lain langsung tidak dijumpai —
     * id yang disuap terus ke dalam URL tidak mendedahkan mahupun mengubah
     * apa-apa.
     *
     * Hanya imbasan draf diterima. Imbasan yang telah disahkan sudah menjana
     * pergerakan stok, dan menukar padanannya selepas itu akan menjadikan
     * rekod imbasan tidak lagi sepadan dengan stok yang telah direkodkan.
     */
    private function barisImbasan(?int $id): ?InvoiceScanItem
    {
        if (! $id) {
            return null;
        }

        return InvoiceScanItem::whereKey($id)
            ->whereHas('invoiceScan', fn ($q) => $q->where('status', 'draf'))
            ->first();
    }

    /** Menyimpan gambar yang dimuat naik dan memulangkan laluannya, atau null jika tiada. */
    private function simpanGambar(Request $request): ?string
    {
        return $request->hasFile('gambar')
            ? $request->file('gambar')->store('produk', 'local')
            : null;
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')
                ->where('workspace_id', $request->user()->workspace_id)
                ->ignore($product)],
            // Barcode tidak wajib: banyak produk SME dibungkus sendiri dan tiada
            // kod tercetak. Yang ada mesti unik, kerana imbasan mencari satu
            // produk dan bukan senarai.
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')
                ->where('workspace_id', $request->user()->workspace_id)
                ->ignore($product)],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:'.self::SAIZ_GAMBAR_KB],
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            // Berskop supaya kategori atau pembekal syarikat lain tidak boleh
            // dipautkan dengan menyuap id secara terus.
            'category_id' => ['nullable', Rule::exists('categories', 'id')
                ->where('workspace_id', $request->user()->workspace_id)],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')
                ->where('workspace_id', $request->user()->workspace_id)],
            'unit' => ['required', 'string', 'max:20'],
            'harga_kos' => ['required', 'numeric', 'min:0'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'jejak_batch' => ['nullable', 'boolean'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        $data['aktif'] = $request->boolean('aktif');
        $data['jejak_batch'] = $request->boolean('jejak_batch');

        // Gambar dikendalikan berasingan kerana ia fail, bukan nilai lajur.
        unset($data['gambar']);

        // Stok hanya boleh diubah melalui modul Pergerakan Stok supaya jejak audit kekal utuh.
        unset($data['stok']);

        return $data;
    }
}
