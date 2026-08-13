<?php

namespace App\Services\Stok;

use App\Models\Product;
use App\Models\StockBalance;
use RuntimeException;

/**
 * Satu-satunya tempat baki per lokasi diubah.
 *
 * Empat aliran menyentuh stok — pergerakan manual, pengesahan imbasan invois,
 * pengesahan kiraan stok, dan pemindahan antara gudang. Kalau setiap satu
 * mengira barisnya sendiri, satu daripadanya akan terlupa mengunci baris atau
 * terlupa menyemak baki negatif, dan pepijat begitu hanya kelihatan selepas
 * angka gudang tidak lagi masuk akal.
 *
 * Jumlah keseluruhan pada `products.stok` sengaja tidak disentuh di sini.
 * Pemindahan mengubah baki lokasi tanpa mengubah jumlah, jadi kedua-duanya
 * memang dua fakta berasingan: berapa banyak yang ada, dan di mana ia berada.
 */
class BakiLokasi
{
    /**
     * Menambah atau menolak baki satu produk pada satu lokasi.
     *
     * @param  int  $delta  Positif menambah, negatif menolak.
     *
     * @throws RuntimeException apabila penolakan melebihi baki lokasi itu.
     */
    public static function laraskan(Product $product, int $locationId, int $delta): StockBalance
    {
        // Baris dikunci sebelum dibaca supaya dua penghantaran serentak dari
        // gudang yang sama tidak boleh membaca baki yang sama dan menolaknya dua kali.
        $baki = StockBalance::lockForUpdate()->firstOrNew([
            'product_id' => $product->id,
            'location_id' => $locationId,
        ]);

        $selepas = ($baki->kuantiti ?? 0) + $delta;

        if ($selepas < 0) {
            throw new RuntimeException(__('wky.flash.stok_lokasi_tidak_cukup', [
                'baki' => $baki->kuantiti ?? 0,
                'unit' => $product->unit,
            ]));
        }

        $baki->kuantiti = $selepas;
        $baki->save();

        return $baki;
    }

    /** Menetapkan baki lokasi kepada nilai tepat, dan memulangkan perbezaannya. */
    public static function tetapkan(Product $product, int $locationId, int $kuantiti): int
    {
        $baki = StockBalance::lockForUpdate()->firstOrNew([
            'product_id' => $product->id,
            'location_id' => $locationId,
        ]);

        $beza = $kuantiti - ($baki->kuantiti ?? 0);

        $baki->kuantiti = $kuantiti;
        $baki->save();

        return $beza;
    }

    public static function baki(Product $product, int $locationId): int
    {
        return (int) StockBalance::where('product_id', $product->id)
            ->where('location_id', $locationId)
            ->value('kuantiti');
    }
}
