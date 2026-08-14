<?php

namespace App\Services\Stok;

use App\Models\Product;
use App\Models\ProductBatch;

/**
 * Lot penerimaan bagi produk yang dijejak batchnya.
 *
 * Dua aliran menerima barang daripada pembekal — pengesahan imbasan invois dan
 * penerimaan Purchase Order — dan kedua-duanya menghadapi masalah yang sama:
 * dokumen pembekal tidak membawa nombor batch. Satu penghantaran dianggap satu
 * lot dan dinamakan mengikut rujukan dokumen itu.
 *
 * Tanpa ini, baki batch akan tertinggal di belakang baki produk setiap kali
 * stok masuk melalui aliran tersebut — dan angka batch yang tidak boleh
 * dipercayai lebih memudaratkan daripada tiada batch langsung.
 *
 * Tarikh luput dibiarkan kosong kerana ia memang tidak diketahui di sini; ia
 * diisi pada halaman produk selepas kotak sebenar diperiksa.
 */
class LotPenerimaan
{
    /**
     * Menambah kuantiti ke dalam lot bernama, dan menyerap kosnya.
     *
     * @param  string  $noBatch  Rujukan dokumen penerimaan, dijadikan nombor lot
     * @return ProductBatch|null  Null apabila produk itu tidak dijejak batchnya
     */
    public static function serap(Product $product, string $noBatch, int $kuantiti, ?float $kos = null): ?ProductBatch
    {
        if (! $product->jejak_batch) {
            return null;
        }

        $batch = ProductBatch::lockForUpdate()->firstOrNew([
            'product_id' => $product->id,
            'no_batch' => $noBatch,
        ]);

        // Kos diserap sebelum kuantiti dinaikkan: purata berwajaran memerlukan
        // baki lama lot itu, yang hilang selepas kenaikan.
        $sedia = (int) ($batch->kuantiti ?? 0);
        $batch->serapKos($sedia, $kuantiti, $kos);

        $batch->kuantiti = $sedia + $kuantiti;
        $batch->save();

        return $batch;
    }
}
