<?php

namespace App\Http\Controllers;

use App\Models\Category;
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

    public function create(): View
    {
        return view('products.form', [
            'product' => new Product(['unit' => 'unit', 'aktif' => true]),
            'categories' => Category::orderBy('nama')->get(),
            'suppliers' => Supplier::orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Product::create($this->validated($request));

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
