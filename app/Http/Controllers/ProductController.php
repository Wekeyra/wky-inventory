<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InvoiceScanItem;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $cari = $request->string('cari')->toString();

        $products = Product::query()
            ->with(['category', 'supplier'])
            ->when($cari, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('sku', 'like', "%{$cari}%")))
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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::create($this->validated($request));

        $baris = $this->barisImbasan($request->integer('baris_imbasan'));

        // Produk yang baharu dicipta tidak akan dipadankan sendiri: padanan
        // berlaku sekali sahaja semasa AI membaca invois. Tanpa langkah ini
        // pengguna terpaksa memilih semula produk yang baru sahaja dia cipta.
        if ($baris !== null) {
            $baris->update(['product_id' => $product->id, 'kaedah_padanan' => 'manual']);

            return redirect()->route('invoice-scans.show', $baris->invoice_scan_id)
                ->with('status', __('wky.flash.produk_tambah_padan', ['nama' => $product->nama]));
        }

        return redirect()->route('products.index')->with('status', __('wky.flash.produk_tambah'));
    }

    public function show(Product $product): View
    {
        $product->load('category', 'supplier');

        return view('products.show', [
            'product' => $product,
            'movements' => $product->movements()->with('user')->latest()->paginate(15),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products.form', [
            'product' => $product,
            'barisImbasan' => null,
            'categories' => Category::orderBy('nama')->get(),
            'suppliers' => Supplier::orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return redirect()->route('products.index')->with('status', __('wky.flash.produk_kemas_kini'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('products.index')->with('status', __('wky.flash.produk_padam'));
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

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')
                ->where('workspace_id', $request->user()->workspace_id)
                ->ignore($product)],
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
            'aktif' => ['nullable', 'boolean'],
        ]);

        $data['aktif'] = $request->boolean('aktif');

        // Stok hanya boleh diubah melalui modul Pergerakan Stok supaya jejak audit kekal utuh.
        unset($data['stok']);

        return $data;
    }
}
