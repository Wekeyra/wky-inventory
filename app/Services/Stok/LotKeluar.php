<?php

namespace App\Services\Stok;

use App\Models\Product;
use App\Models\ProductBatch;
use RuntimeException;

/**
 * Menolak baki satu lot apabila barangnya keluar.
 *
 * Dua aliran mengeluarkan stok daripada lot — borang pergerakan stok dan modul
 * jualan — dan kedua-duanya perlu semakan baki yang sama. Semakan yang ditulis
 * dua kali ialah semakan yang akan wujud dalam satu tempat sahaja selepas
 * seseorang membetulkan salah satunya.
 */
class LotKeluar
{
    /**
     * @throws RuntimeException apabila lot tidak cukup untuk kuantiti itu
     */
    public static function ambil(ProductBatch $batch, int $kuantiti, Product $product): ProductBatch
    {
        // Dibaca semula dengan kunci: lot yang sama boleh dijual oleh dua orang
        // pada masa yang sama, dan semakan terhadap salinan lama akan
        // membenarkan kedua-duanya lulus.
        $lot = ProductBatch::lockForUpdate()->findOrFail($batch->id);

        if ($lot->kuantiti < $kuantiti) {
            throw new RuntimeException(__('wky.flash.batch_tidak_cukup', [
                'batch' => $lot->no_batch,
                'baki' => $lot->kuantiti,
                'unit' => $product->unit,
            ]));
        }

        $lot->decrement('kuantiti', $kuantiti);

        return $lot;
    }
}
