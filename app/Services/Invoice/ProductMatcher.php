<?php

namespace App\Services\Invoice;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Memadankan baris invois dengan produk dalam sistem.
 *
 * Padanan sengaja dihadkan kepada padanan tepat pada SKU atau nama (selepas
 * penormalan huruf besar/kecil, ruang, dan tanda baca). Padanan kabur tidak
 * digunakan kerana padanan yang salah akan melaraskan stok produk yang tidak
 * berkaitan tanpa disedari — baris yang meragukan lebih baik diserahkan kepada
 * pengguna untuk dipilih sendiri.
 */
class ProductMatcher
{
    /** @var Collection<string, Product>|null */
    private ?Collection $indeksSku = null;

    /** @var Collection<string, Product>|null */
    private ?Collection $indeksNama = null;

    /**
     * @return array{product: Product|null, kaedah: string}
     */
    public function match(ExtractedLine $baris): array
    {
        $this->muatIndeks();

        if ($baris->sku !== null) {
            $produk = $this->indeksSku->get($this->normal($baris->sku));

            if ($produk !== null) {
                return ['product' => $produk, 'kaedah' => 'sku'];
            }
        }

        $produk = $this->indeksNama->get($this->normal($baris->nama));

        if ($produk !== null) {
            return ['product' => $produk, 'kaedah' => 'nama'];
        }

        return ['product' => null, 'kaedah' => 'tiada'];
    }

    /**
     * Memasukkan produk yang baru dicipta ke dalam indeks yang sedang digunakan.
     *
     * Indeks dimuatkan sekali sahaja untuk satu imbasan, jadi tanpa ini baris
     * kedua yang membawa kod yang sama tidak akan nampak produk yang baru
     * dicipta oleh baris pertama — ia akan cuba menciptanya sekali lagi dan
     * melanggar keunikan SKU di tengah-tengah imbasan.
     */
    public function daftar(Product $product): void
    {
        $this->muatIndeks();

        $this->indeksSku->put($this->normal($product->sku), $product);
        $this->indeksNama->put($this->normal($product->nama), $product);
    }

    private function muatIndeks(): void
    {
        if ($this->indeksSku !== null) {
            return;
        }

        $produk = Product::query()->get(['id', 'sku', 'nama', 'unit', 'stok']);

        // keyBy mengekalkan kemasukan terakhir apabila kunci berulang; produk
        // disusun mengikut id supaya hasil padanan kekal boleh diramal.
        $this->indeksSku = $produk->sortBy('id')->keyBy(fn (Product $item) => $this->normal($item->sku));
        $this->indeksNama = $produk->sortBy('id')->keyBy(fn (Product $item) => $this->normal($item->nama));
    }

    private function normal(string $teks): string
    {
        $teks = mb_strtolower(trim($teks));

        // Buang segala yang bukan huruf atau nombor supaya "ELK-001", "elk 001",
        // dan "ELK_001" dianggap kod yang sama.
        return preg_replace('/[^\p{L}\p{N}]+/u', '', $teks) ?? $teks;
    }
}
