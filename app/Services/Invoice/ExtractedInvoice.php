<?php

namespace App\Services\Invoice;

/** Hasil bacaan satu dokumen invois. */
final readonly class ExtractedInvoice
{
    /**
     * @param  list<ExtractedLine>  $barang
     */
    public function __construct(
        public ?string $noInvois,
        public ?string $tarikhInvois,
        public ?string $namaPembekal,
        public array $barang,
    ) {}
}
