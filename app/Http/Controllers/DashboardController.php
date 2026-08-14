<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\Stok\NilaiStok;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'jumlahProduk' => Product::count(),
            'jumlahKategori' => Category::count(),
            'jumlahPembekal' => Supplier::count(),
            'nilaiStok' => NilaiStok::kini(),
            'stokRendah' => Product::stokRendah()->with('category')->orderBy('stok')->limit(10)->get(),
            // Lot yang sudah luput disenaraikan sekali dengan yang hampir luput:
            // barang yang terlepas tarikhnya masih di rak sehingga seseorang
            // mengeluarkannya, jadi ia perlu kekal kelihatan.
            'batchLuput' => ProductBatch::query()
                ->adaBaki()
                ->hampirLuput()
                ->with('product')
                ->orderBy('tarikh_luput')
                ->limit(10)
                ->get(),
            'pergerakanTerkini' => StockMovement::with(['product', 'user'])->latest()->limit(10)->get(),
            'ringkasanBulanan' => $this->ringkasanBulanan(),
            'produkAktif' => Product::where('aktif', true)->orderBy('nama')->get(),
            // Borang pantas menghantar ke laluan yang sama seperti borang penuh,
            // jadi ia perlu senarai sebab dan lokasi yang sama juga.
            'sebabPilihan' => StockMovementController::sebabPilihan(),
            'locations' => Location::aktif()->orderByDesc('lalai')->orderBy('nama')->get(),
            'lokasiLalai' => Location::lalai()?->id,
            'sesiKiraan' => StockCount::query()
                ->with('pembuka')
                ->withCount([
                    'items',
                    'items as items_dikira_count' => fn ($q) => $q->whereNotNull('kuantiti_fizikal'),
                ])
                ->latest()
                ->limit(5)
                ->get(),
            'kiraanDraf' => StockCount::where('status', 'draf')->count(),
        ]);
    }

    /**
     * Jumlah stok masuk vs keluar bagi enam bulan terakhir, untuk carta Ringkasan Bulanan.
     *
     * Pengumpulan dibuat dalam PHP dan bukan SQL kerana sintaks fungsi tarikh berbeza
     * antara MySQL (aplikasi) dan SQLite (ujian).
     *
     * @return array{label: list<string>, masuk: list<int>, keluar: list<int>}
     */
    private function ringkasanBulanan(): array
    {
        $mula = Carbon::now()->startOfMonth()->subMonths(5);

        $rekod = StockMovement::query()
            ->where('created_at', '>=', $mula)
            ->whereIn('jenis', ['masuk', 'keluar'])
            ->get(['jenis', 'kuantiti', 'created_at'])
            ->groupBy(fn (StockMovement $gerak) => $gerak->created_at->format('Y-m'));

        $label = [];
        $masuk = [];
        $keluar = [];

        for ($i = 0; $i < 6; $i++) {
            $bulan = $mula->copy()->addMonths($i);
            $kumpulan = $rekod->get($bulan->format('Y-m'), new Collection());

            $label[] = $bulan->translatedFormat('M Y');
            $masuk[] = (int) $kumpulan->where('jenis', 'masuk')->sum('kuantiti');
            $keluar[] = (int) $kumpulan->where('jenis', 'keluar')->sum('kuantiti');
        }

        return compact('label', 'masuk', 'keluar');
    }
}
