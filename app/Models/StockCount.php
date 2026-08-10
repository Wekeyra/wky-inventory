<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCount extends Model
{
    use HasFactory;

    protected $fillable = [
        'kod',
        'status',
        'category_id',
        'dibuka_oleh',
        'disahkan_oleh',
        'disahkan_pada',
        'catatan',
    ];

    protected function casts(): array
    {
        return ['disahkan_pada' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function pembuka(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuka_oleh');
    }

    public function pengesah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disahkan_oleh');
    }

    public function isDraf(): bool
    {
        return $this->status === 'draf';
    }

    public function labelStatus(): string
    {
        return match ($this->status) {
            'draf' => 'Draf',
            'selesai' => 'Selesai',
            default => 'Dibatalkan',
        };
    }

    public function warnaStatus(): string
    {
        return match ($this->status) {
            'draf' => 'warning',
            'selesai' => 'success',
            default => 'secondary',
        };
    }
}
