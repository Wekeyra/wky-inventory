<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'jenis',
        'kuantiti',
        'stok_sebelum',
        'stok_selepas',
        'rujukan',
        'catatan',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function labelJenis(): string
    {
        return __('wky.stok.' . ($this->jenis === 'pelarasan' ? 'pelarasan' : $this->jenis));
    }

    /** Nama kelas penuh supaya Tailwind dapat mengesannya semasa membina CSS. */
    public function kelasJenis(): string
    {
        return match ($this->jenis) {
            'masuk' => 'lencana-hijau',
            'keluar' => 'lencana-merah',
            default => 'lencana-kuning',
        };
    }
}
