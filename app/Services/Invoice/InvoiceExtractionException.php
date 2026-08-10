<?php

namespace App\Services\Invoice;

use RuntimeException;

/** Dilempar apabila invois tidak dapat dibaca; mesejnya dipaparkan kepada pengguna. */
class InvoiceExtractionException extends RuntimeException {}
