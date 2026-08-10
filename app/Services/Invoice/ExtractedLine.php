<?php

namespace App\Services\Invoice;

/** Satu baris barang seperti yang dibaca daripada invois. */
final readonly class ExtractedLine
{
    public function __construct(
        public ?string $sku,
        public string $nama,
        public int $kuantiti,
        public ?float $hargaUnit,
    ) {}
}
