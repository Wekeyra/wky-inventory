<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        $movements = StockMovement::query()
            ->with(['product', 'user'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis', $request->string('jenis')->toString()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('stock.index', [
            'movements' => $movements,
            'products' => Product::orderBy('nama')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('stock.form', [
            'products' => Product::where('aktif', true)->orderBy('nama')->get(),
            'terpilih' => $request->integer('product_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')
                ->where('workspace_id', $request->user()->workspace_id)],
            'jenis' => ['required', 'in:masuk,keluar,pelarasan'],
            'kuantiti' => ['required', 'integer', 'min:1'],
            'rujukan' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($data, $request) {
                // lockForUpdate menghalang dua pengguna mengemas kini stok yang sama serentak.
                $product = Product::lockForUpdate()->findOrFail($data['product_id']);

                $sebelum = $product->stok;
                $selepas = match ($data['jenis']) {
                    'masuk' => $sebelum + $data['kuantiti'],
                    'keluar' => $sebelum - $data['kuantiti'],
                    default => $data['kuantiti'],
                };

                if ($selepas < 0) {
                    throw new \RuntimeException(
                        __('wky.flash.stok_tidak_cukup', ['baki' => $sebelum, 'unit' => $product->unit])
                    );
                }

                $product->update(['stok' => $selepas]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()?->id,
                    'jenis' => $data['jenis'],
                    'kuantiti' => $data['kuantiti'],
                    'stok_sebelum' => $sebelum,
                    'stok_selepas' => $selepas,
                    'rujukan' => $data['rujukan'] ?? null,
                    'catatan' => $data['catatan'] ?? null,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('ralat', $e->getMessage());
        }

        // Borang pantas dipanggil dari dashboard, jadi pengguna dikembalikan ke sana.
        $destinasi = $request->input('sumber') === 'pantas' ? 'dashboard' : 'stock.index';

        return redirect()->route($destinasi)->with('status', __('wky.flash.stok_direkod'));
    }
}
