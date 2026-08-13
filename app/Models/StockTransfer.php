<?php

namespace App\Models;

use App\Models\Concerns\MilikRuangKerja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasFactory, MilikRuangKerja;

    protected $fillable = [
        'workspace_id',
        'kod',
        'status',
        'location_asal_id',
        'location_tujuan_id',
        'dihantar_oleh',
        'diterima_oleh',
        'diterima_pada',
        'catatan',
    ];

    protected function casts(): array
    {
        return ['diterima_pada' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function asal(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_asal_id');
    }

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_tujuan_id');
    }

    public function penghantar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dihantar_oleh');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_oleh');
    }

    /** Stok sudah keluar dari gudang asal tetapi belum diterima di gudang tujuan. */
    public function dalamPerjalanan(): bool
    {
        return $this->status === 'dalam_perjalanan';
    }

    public function jumlahUnit(): int
    {
        return (int) $this->items->sum('kuantiti');
    }

    public function labelStatus(): string
    {
        return __('wky.pindah.status_' . $this->status);
    }

    /** Nama kelas penuh supaya Tailwind dapat mengesannya semasa membina CSS. */
    public function kelasStatus(): string
    {
        return match ($this->status) {
            'dalam_perjalanan' => 'lencana-kuning',
            'selesai' => 'lencana-hijau',
            default => 'lencana-kelabu',
        };
    }
}
