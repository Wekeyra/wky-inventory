<?php

namespace App\Models;

use App\Models\Concerns\MilikRuangKerja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCount extends Model
{
    use HasFactory, MilikRuangKerja;

    protected $fillable = [
        'workspace_id',
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
        return __('wky.kiraan.status_' . $this->status);
    }

    /** Nama kelas penuh supaya Tailwind dapat mengesannya semasa membina CSS. */
    public function kelasStatus(): string
    {
        return match ($this->status) {
            'draf' => 'lencana-kuning',
            'selesai' => 'lencana-hijau',
            default => 'lencana-kelabu',
        };
    }
}
