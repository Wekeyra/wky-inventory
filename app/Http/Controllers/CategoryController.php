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

    public function create(): View
    {
        return view('categories.form', ['category' => new Category()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return redirect()->route('categories.index')->with('status', __('wky.flash.kategori_tambah'));
    }

    public function edit(Category $category): View
    {
        return view('categories.form', compact('category'));
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
            'kod' => ['required', 'string', 'max:20', Rule::unique('categories', 'kod')->ignore($category)],
            'keterangan' => ['nullable', 'string'],
        ]);
    }
}
