<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCountItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_count_id',
        'product_id',
        'kuantiti_rekod',
        'kuantiti_fizikal',
    ];

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Perbezaan antara kiraan fizikal dan baki rekod; null jika belum dikira. */
    public function beza(): ?int
    {
        return $this->kuantiti_fizikal === null
            ? null
            : $this->kuantiti_fizikal - $this->kuantiti_rekod;
    }

    public function sudahDikira(): bool
    {
        return $this->kuantiti_fizikal !== null;
    }
}
