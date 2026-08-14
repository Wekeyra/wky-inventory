<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\Stok\NilaiStok;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function monthly(Request $request): View
    {
        $request->validate([
            'bulan' => ['nullable', 'date_format:Y-m'],
        ]);

        $bulan = Carbon::createFromFormat('Y-m', $request->input('bulan', Carbon::now()->format('Y-m')))->startOfMonth();
        $tamat = $bulan->copy()->endOfMonth();

        $pergerakan = StockMovement::query()
            ->whereBetween('created_at', [$bulan, $tamat])
            ->with('product:id,sku,nama,unit,stok,harga_kos')
            ->get();

        $baris = $pergerakan
            ->groupBy('product_id')
            ->map(fn ($kumpulan) => [
                'produk' => $kumpulan->first()->product,
                'masuk' => (int) $kumpulan->where('jenis', 'masuk')->sum('kuantiti'),
                'keluar' => (int) $kumpulan->where('jenis', 'keluar')->sum('kuantiti'),
                'pelarasan' => $kumpulan->where('jenis', 'pelarasan')->count(),
                'bil_transaksi' => $kumpulan->count(),
            ])
            ->filter(fn ($baris) => $baris['produk'] !== null)
            ->sortBy(fn ($baris) => $baris['produk']->nama)
            ->values();

        return view('reports.monthly', [
            'bulan' => $bulan,
            'baris' => $baris,
            'jumlahMasuk' => $baris->sum('masuk'),
            'jumlahKeluar' => $baris->sum('keluar'),
            'jumlahTransaksi' => $pergerakan->count(),
            'nilaiStokSemasa' => NilaiStok::kini(),
            'pilihanBulan' => $this->pilihanBulan(),
        ]);
    }

    /**
     * Dua belas bulan terakhir untuk pemilih bulan.
     *
     * @return array<string, string>
     */
    private function pilihanBulan(): array
    {
        $pilihan = [];

        for ($i = 0; $i < 12; $i++) {
            $bulan = Carbon::now()->startOfMonth()->subMonths($i);
            $pilihan[$bulan->format('Y-m')] = $bulan->translatedFormat('F Y');
        }

        return $pilihan;
    }
}
