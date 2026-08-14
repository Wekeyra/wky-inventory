<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\Stok\NilaiStok;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Analitik inventori: reorder, produk terlaris, pusing ganti, dan stok mati.
 *
 * Setiap angka di sini dikira daripada pergerakan stok sebenar dan bukan
 * daripada nombor ringkasan yang disimpan. Ringkasan yang disimpan akan
 * terpesong, dan angka analitik yang senyap-senyap salah lebih memudaratkan
 * daripada tiada analitik langsung — ia kelihatan sama meyakinkan.
 */
class AnalyticsController extends Controller
{
    /** Tempoh yang boleh dipilih, dalam hari. */
    public const TEMPOH = [30, 60, 90, 180, 365];

    /** Hari tanpa pergerakan keluar sebelum stok dikira mati. */
    public const HARI_MATI = 90;

    public function index(Request $request): View
    {
        $request->validate(['hari' => ['nullable', 'integer']]);

        $hari = in_array($request->integer('hari'), self::TEMPOH, true)
            ? $request->integer('hari')
            : 90;

        $mula = Carbon::now()->subDays($hari);

        // Satu pertanyaan bagi semua pengeluaran dalam tempoh; setiap analitik
        // di bawah membaca daripada koleksi yang sama supaya angka-angkanya
        // tidak boleh bercanggah antara satu sama lain.
        $keluar = StockMovement::query()
            ->where('jenis', 'keluar')
            ->where('created_at', '>=', $mula)
            ->get(['product_id', 'kuantiti', 'kos_seunit', 'created_at']);

        $keluarMengikutProduk = $keluar->groupBy('product_id')
            ->map(fn (Collection $kumpulan) => (int) $kumpulan->sum('kuantiti'));

        return view('analytics.index', [
            'hari' => $hari,
            'tempohPilihan' => self::TEMPOH,
            'reorder' => $this->reorder($keluarMengikutProduk, $hari),
            'terlaris' => $this->terlaris($mula),
            'pusingGanti' => $this->pusingGanti($keluar, $hari),
            'stokMati' => $this->stokMati(),
            'hariMati' => self::HARI_MATI,
        ]);
    }

    /**
     * Produk yang perlu dipesan semula.
     *
     * Cadangan kuantiti dikira daripada kadar penggunaan sebenar dan bukan
     * daripada paras minimum sahaja: dua produk yang paras minimumnya sama
     * tetapi bergerak pada kelajuan berbeza tidak sepatutnya dipesan dalam
     * kuantiti yang sama.
     *
     * Formulanya ialah penggunaan 30 hari, ditambah jurang untuk mencapai
     * semula paras minimum. Produk yang tidak bergerak langsung dicadangkan
     * hanya sebanyak jurang itu — membeli lebih banyak barang yang tidak
     * bergerak ialah cara paling cepat menukar wang tunai menjadi stok mati.
     *
     * @param  Collection<int, int>  $keluarMengikutProduk
     * @return Collection<int, array<string, mixed>>
     */
    private function reorder(Collection $keluarMengikutProduk, int $hari): Collection
    {
        return Product::query()
            ->where('aktif', true)
            ->stokRendah()
            ->with('supplier')
            ->orderBy('nama')
            ->get()
            ->map(function (Product $produk) use ($keluarMengikutProduk, $hari) {
                $digunakan = (int) ($keluarMengikutProduk[$produk->id] ?? 0);
                $sebulan = $hari > 0 ? $digunakan / $hari * 30 : 0;
                $jurang = max(0, $produk->stok_minimum - $produk->stok);

                return [
                    'produk' => $produk,
                    'digunakan' => $digunakan,
                    'sebulan' => (int) ceil($sebulan),
                    'cadangan' => (int) ceil($sebulan) + $jurang,
                ];
            });
    }

    /**
     * Produk paling laris mengikut untung kasar, bukan kuantiti.
     *
     * Kuantiti sahaja menaikkan barang murah yang bergerak pantas ke atas
     * senarai walaupun ia hampir tidak menyumbang apa-apa. Kedua-dua angka
     * dipaparkan supaya perbezaan itu kelihatan.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function terlaris(Carbon $mula): Collection
    {
        return SaleItem::query()
            ->whereHas('sale', fn ($q) => $q->where('created_at', '>=', $mula))
            ->with('product:id,sku,nama,unit')
            ->get()
            ->groupBy('product_id')
            ->map(fn (Collection $baris) => [
                'produk' => $baris->first()->product,
                'kuantiti' => (int) $baris->sum('kuantiti'),
                'jualan' => (float) $baris->sum(fn (SaleItem $item) => $item->nilaiJualan()),
                'untung' => (float) $baris->sum(fn (SaleItem $item) => $item->untung() ?? 0),
                'kosPenuh' => $baris->every(fn (SaleItem $item) => $item->kos_seunit !== null),
            ])
            ->filter(fn (array $baris) => $baris['produk'] !== null)
            ->sortByDesc('untung')
            ->take(10)
            ->values();
    }

    /**
     * Pusing ganti inventori: kos barang yang keluar berbanding nilai stok.
     *
     * Nilai stok semasa digunakan sebagai penyebut dan bukan purata inventori
     * sepanjang tempoh. Purata sebenar memerlukan gambaran nilai stok setiap
     * hari, yang tidak disimpan — dan menganggarkannya daripada baki hari ini
     * akan menghasilkan nombor yang kelihatan tepat tanpa menjadi tepat.
     * Batasan ini dinyatakan pada halaman itu sendiri.
     *
     * @param  Collection<int, StockMovement>  $keluar
     * @return array<string, float|int|bool>
     */
    private function pusingGanti(Collection $keluar, int $hari): array
    {
        $kosKeluar = (float) $keluar->sum(fn (StockMovement $gerak) => $gerak->nilaiKos() ?? 0);
        $nilaiStok = NilaiStok::kini();

        // Pergerakan tanpa kos menyumbang sifar, jadi pusing ganti akan nampak
        // lebih perlahan daripada yang sebenar. Halaman menandakannya.
        return [
            'kosKeluar' => $kosKeluar,
            'nilaiStok' => $nilaiStok,
            'kadar' => $nilaiStok > 0 ? $kosKeluar / $nilaiStok : 0.0,
            // Hari untuk menjual stok sedia ada pada kadar tempoh ini.
            'hariStok' => $kosKeluar > 0 ? $nilaiStok / ($kosKeluar / $hari) : null,
            'kosLengkap' => ! $keluar->contains(fn (StockMovement $gerak) => $gerak->kos_seunit === null),
        ];
    }

    /**
     * Stok yang masih ada baki tetapi tidak bergerak keluar.
     *
     * Produk yang tidak pernah bergerak langsung turut disenaraikan: barang
     * yang dibeli dan tidak pernah dijual ialah kes stok mati yang paling
     * jelas, dan ia tiada pergerakan untuk ditapis mengikut tarikh.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function stokMati(): Collection
    {
        $had = Carbon::now()->subDays(self::HARI_MATI);

        $keluarTerakhir = StockMovement::query()
            ->where('jenis', 'keluar')
            ->selectRaw('product_id, MAX(created_at) as terakhir')
            ->groupBy('product_id')
            ->pluck('terakhir', 'product_id');

        return Product::query()
            ->where('aktif', true)
            ->where('stok', '>', 0)
            ->orderByDesc('stok')
            ->get()
            ->map(function (Product $produk) use ($keluarTerakhir) {
                $terakhir = $keluarTerakhir[$produk->id] ?? null;

                return [
                    'produk' => $produk,
                    'terakhir' => $terakhir === null ? null : Carbon::parse($terakhir),
                    'nilai' => (float) $produk->harga_kos * $produk->stok,
                ];
            })
            ->filter(fn (array $baris) => $baris['terakhir'] === null || $baris['terakhir']->lt($had))
            ->sortByDesc('nilai')
            ->take(20)
            ->values();
    }
}
