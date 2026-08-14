<?php

namespace App\Services\Stok;

use App\Models\Product;
use App\Models\ProductBatch;

/**
 * Satu-satunya tempat nilai stok semasa dikira.
 *
 * Dahulunya kedua-dua dashboard dan laporan bulanan menulis `SUM(harga_kos *
 * stok)` sendiri. Formula itu menilai semua stok pada harga kos produk hari
 * ini, jadi menaikkan harga pembekal turut menaikkan nilai stok yang sudah
 * lama berada di rak — stok yang sebenarnya dibeli pada harga lama.
 *
 * Sekarang setiap lot membawa kos penerimaannya sendiri, jadi produk yang
 * dijejak batchnya boleh dinilai pada kos sebenar. Produk lain kekal dinilai
 * pada harga kos produk, kerana tanpa lot memang tiada tempat lain untuk kos
 * itu disimpan.
 */
class NilaiStok
{
    /**
     * Nilai stok semasa bagi ruang kerja pengguna yang log masuk.
     *
     * Dikira sebagai dua bahagian yang tidak bertindih:
     *
     * - Produk **tanpa** jejak batch — `harga_kos × stok`, seperti dahulu.
     * - Produk **dengan** jejak batch — jumlah setiap lot pada kosnya sendiri.
     *
     * Lot yang belum berkos jatuh kepada harga kos produk. Tanpa itu, semua
     * stok yang wujud sebelum kos mula direkod akan terus lenyap daripada
     * jumlah, dan nilai stok akan nampak jatuh mendadak pada hari ciri ini
     * dipasang.
     *
     * Nota: baki lot boleh terpesong daripada `products.stok` — pelarasan
     * menyeluruh tidak menyentuh lot. Apabila itu berlaku, bahagian berbatch
     * di sini menilai apa yang ada **dalam lot**, dan bukan apa yang
     * `products.stok` dakwa. Perbezaan itu sudah pun dipaparkan pada halaman
     * produk.
     */
    public static function kini(): float
    {
        $tanpaLot = (float) Product::query()
            ->where('jejak_batch', false)
            ->selectRaw('SUM(harga_kos * stok) as nilai')
            ->value('nilai');

        $berlot = (float) ProductBatch::query()
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->where('products.jejak_batch', true)
            ->selectRaw('SUM(product_batches.kuantiti * COALESCE(product_batches.kos_seunit, products.harga_kos)) as nilai')
            ->value('nilai');

        return $tanpaLot + $berlot;
    }
}
