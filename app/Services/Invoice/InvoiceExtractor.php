<?php

namespace App\Services\Invoice;

/**
 * Membaca satu dokumen invois dan memulangkan barisnya.
 *
 * Diabstrakkan sebagai antara muka supaya ujian boleh menggantikannya dengan
 * pelaksanaan palsu, dan supaya pembekal AI boleh ditukar tanpa menyentuh
 * controller atau model.
 */
interface InvoiceExtractor
{
    /**
     * @param  string  $kandungan  Bait mentah fail invois.
     * @param  string  $jenisMime  Contoh: image/jpeg, image/png, application/pdf.
     *
     * @throws InvoiceExtractionException
     */
    public function extract(string $kandungan, string $jenisMime): ExtractedInvoice;
}
