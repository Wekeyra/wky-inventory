<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceScanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_scan_id',
        'product_id',
        'sku_invois',
        'nama_invois',
        'kuantiti',
        'harga_unit',
        'kaedah_padanan',
        'dilangkau',
    ];

    protected function casts(): array
    {
        return [
            'harga_unit' => 'decimal:2',
            'dilangkau' => 'boolean',
        ];
    }

    public function invoiceScan(): BelongsTo
    {
        return $this->belongsTo(InvoiceScan::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sudahPadan(): bool
    {
        return $this->product_id !== null;
    }

    /** Baris hanya diproses jika ia dipadankan dan tidak ditanda langkau. */
    public function bolehDiproses(): bool
    {
        return $this->sudahPadan() && ! $this->dilangkau;
    }

    public function labelPadanan(): string
    {
        return __('wky.imbas.padanan_' . $this->kaedah_padanan);
    }

    /** Nama kelas penuh supaya Tailwind dapat mengesannya semasa membina CSS. */
    public function kelasPadanan(): string
    {
        return match ($this->kaedah_padanan) {
            'sku' => 'lencana-hijau',
            'nama' => 'lencana-biru',
            'manual' => 'lencana-kuning',
            'auto' => 'lencana-kuning',
            default => 'lencana-merah',
        };
    }
}
