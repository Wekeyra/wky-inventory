<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->when($request->string('cari')->toString(), fn ($q, $cari) => $q->where('nama', 'like', "%{$cari}%")->orWhere('kod', 'like', "%{$cari}%"))
            ->withCount('products')
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function create(Request $request): View
    {
        return view('categories.form', [
            'category' => new Category(),
            'kembali' => $this->kembali($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        $status = __('wky.flash.kategori_tambah');

        // Kategori yang dicipta daripada penapis halaman Produk pulang ke sana,
        // kerana di situlah kerja pengguna sebenarnya berada — dia berhenti
        // seketika untuk mencipta kategori, bukan datang untuk menguruskannya.
        if ($this->kembali($request) === 'produk') {
            return redirect()->route('products.index')->with('status', $status);
        }

        return redirect()->route('categories.index')->with('status', $status);
    }

    /**
     * Ke mana borang ini patut pulang selepas disimpan.
     *
     * Kata kunci, bukan URL. Menerima URL penuh daripada permintaan bermakna
     * sesiapa boleh menghantar pautan yang mengalihkan pengguna ke tapak lain
     * selepas dia menekan Simpan.
     */
    private function kembali(Request $request): ?string
    {
        return $request->input('kembali') === 'produk' ? 'produk' : null;
    }

    public function edit(Category $category): View
    {
        // Menyunting kategori sedia ada sentiasa bermula daripada senarai
        // kategori, jadi tiada tempat lain untuk pulang.
        return view('categories.form', ['category' => $category, 'kembali' => null]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('categories.index')->with('status', __('wky.flash.kategori_kemas_kini'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('ralat', __('wky.flash.kategori_digunakan'));
        }

        $category->delete();

        return redirect()->route('categories.index')->with('status', __('wky.flash.kategori_padam'));
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            // Kod hanya perlu unik dalam ruang kerja ini; syarikat lain bebas
            // menggunakan kod yang sama.
            'kod' => ['required', 'string', 'max:20', Rule::unique('categories', 'kod')
                ->where('workspace_id', $request->user()->workspace_id)
                ->ignore($category)],
            'keterangan' => ['nullable', 'string'],
        ]);
    }
}
