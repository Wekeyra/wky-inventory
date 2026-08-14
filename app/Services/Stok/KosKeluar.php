<?php

namespace App\Services\Stok;

use App\Models\Product;
use App\Models\ProductBatch;

/**
 * Kos barang yang keluar daripada stok.
 *
 * Dua aliran mengeluarkan stok — borang pergerakan stok dan modul jualan — dan
 * kedua-duanya mesti menjawab soalan yang sama dengan cara yang sama. Kalau
 * satu daripadanya mengira kosnya sendiri, COGS pada laporan tidak akan sepadan
 * dengan nilai pergerakan yang membentuknya.
 */
class KosKeluar
{
    /**
     * Kos seunit bagi kuantiti yang keluar, atau null kalau ia tidak diketahui.
     *
     * Lot yang dipilih memberi jawapan yang tepat: kita tahu dengan pasti unit
     * mana yang keluar, jadi tiada anggaran diperlukan dan tiada kaedah kos
     * seperti FIFO yang perlu dipilih.
     *
     * Tanpa lot, harga kos semasa produk ialah anggaran terbaik yang ada. Ia
     * tetap lebih baik daripada tiada apa-apa, kerana ia sekurang-kurangnya
     * dibekukan pada masa pergerakan itu berlaku dan tidak berubah selepas itu.
     *
     * Sifar dipulangkan sebagai null: harga_kos lalainya 0, jadi sifar bermakna
     * "belum ditetapkan" dan bukan "percuma". Merekodkannya sebagai 0 akan
     * mendakwa barang itu tidak berkos, dan COGS yang terhasil akan menunjukkan
     * untung kasar yang menyamai keseluruhan jualan.
     */
    public static function bagi(Product $product, ?ProductBatch $batch = null): ?float
    {
        if ($batch?->kos_seunit !== null) {
            return (float) $batch->kos_seunit;
        }

        $harga = (float) $product->harga_kos;

        return $harga > 0 ? $harga : null;
    }
}
