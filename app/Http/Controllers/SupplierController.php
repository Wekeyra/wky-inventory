<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $cari = $request->string('cari')->toString();

        $suppliers = Supplier::query()
            ->when($cari, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('kod', 'like', "%{$cari}%")
                ->orWhere('pegawai_perhubungan', 'like', "%{$cari}%")))
            ->withCount('products')
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'cari'));
    }

    public function create(): View
    {
        return view('suppliers.form', ['supplier' => new Supplier(['aktif' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::create($this->validated($request));

        return redirect()->route('suppliers.index')->with('status', 'Pembekal berjaya ditambah.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load(['products' => fn ($q) => $q->orderBy('nama')]);

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request, $supplier));

        return redirect()->route('suppliers.index')->with('status', 'Pembekal berjaya dikemas kini.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->products()->exists()) {
            return back()->with('ralat', 'Pembekal ini masih dikaitkan dengan produk dan tidak boleh dipadam.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('status', 'Pembekal berjaya dipadam.');
    }

    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kod' => ['required', 'string', 'max:20', Rule::unique('suppliers', 'kod')->ignore($supplier)],
            'pegawai_perhubungan' => ['nullable', 'string', 'max:255'],
            'telefon' => ['nullable', 'string', 'max:30'],
            'emel' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        $data['aktif'] = $request->boolean('aktif');

        return $data;
    }
}
