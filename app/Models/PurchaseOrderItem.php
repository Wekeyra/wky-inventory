<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris pada permohonan/PO.
 *
 * Tiada MilikRuangKerja di sini: baris hanya wujud melalui PO induknya, yang
 * sudah berskop ruang kerja, dan menambah skop kedua bermakna baris boleh
 * hilang daripada PO yang masih kelihatan.
 */
class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'kuantiti',
        'kos_seunit',
        'kuantiti_diterima',
    ];

    protected function casts(): array
    {
        return ['kos_seunit' => 'decimal:2'];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Berapa banyak lagi yang belum sampai. */
    public function bakiTerima(): int
    {
        return max(0, $this->kuantiti - $this->kuantiti_diterima);
    }

    /**
     * Nilai baris ini, atau null kalau kosnya tidak ditetapkan.
     *
     * Null dan bukan 0, atas sebab yang sama seperti kos pada pergerakan stok:
     * "kos tidak diketahui" dan "barang percuma" menjumlah kepada angka yang
     * sama, tetapi hanya satu daripadanya benar.
     */
    public function nilai(): ?float
    {
        return $this->kos_seunit === null
            ? null
            : (float) $this->kos_seunit * $this->kuantiti;
    }
}
