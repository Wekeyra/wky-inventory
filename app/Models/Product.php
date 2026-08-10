<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'nama',
        'keterangan',
        'category_id',
        'supplier_id',
        'unit',
        'harga_kos',
        'harga_jual',
        'stok',
        'stok_minimum',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'harga_kos' => 'decimal:2',
            'harga_jual' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** Produk yang stoknya sudah mencecah atau bawah paras minimum. */
    public function scopeStokRendah(Builder $query): Builder
    {
        return $query->whereColumn('stok', '<=', 'stok_minimum');
    }

    public function nilaiStok(): float
    {
        return (float) $this->harga_kos * $this->stok;
    }
}
