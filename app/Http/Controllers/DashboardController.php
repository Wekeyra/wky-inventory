<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'jumlahProduk' => Product::count(),
            'jumlahKategori' => Category::count(),
            'jumlahPembekal' => Supplier::count(),
            'nilaiStok' => Product::query()->selectRaw('SUM(harga_kos * stok) as nilai')->value('nilai') ?? 0,
            'stokRendah' => Product::stokRendah()->with('category')->orderBy('stok')->limit(10)->get(),
            'pergerakanTerkini' => StockMovement::with(['product', 'user'])->latest()->limit(10)->get(),
        ]);
    }
}
