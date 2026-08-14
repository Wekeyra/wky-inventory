<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris jualan.
 *
 * Tiada MilikRuangKerja di sini: baris hanya wujud melalui jualan induknya,
 * yang sudah berskop ruang kerja.
 */
class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_batch_id',
        'kuantiti',
        'harga_jual',
        'kos_seunit',
    ];

    protected function casts(): array
    {
        return [
            'harga_jual' => 'decimal:2',
            'kos_seunit' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    public function nilaiJualan(): float
    {
        return (float) $this->harga_jual * $this->kuantiti;
    }

    /** Null apabila kos barang ini tidak diketahui — bukan sifar. */
    public function nilaiKos(): ?float
    {
        return $this->kos_seunit === null
            ? null
            : (float) $this->kos_seunit * $this->kuantiti;
    }

    /** Untung kasar baris ini, atau null kalau kosnya tidak diketahui. */
    public function untung(): ?float
    {
        $kos = $this->nilaiKos();

        return $kos === null ? null : $this->nilaiJualan() - $kos;
    }
}
