<?php

namespace App\Models;

use App\Models\Concerns\MilikRuangKerja;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, MilikRuangKerja;

    protected $fillable = [
        'workspace_id',
        'sku',
        'barcode',
        'nama',
        'keterangan',
        'laluan_gambar',
        'category_id',
        'supplier_id',
        'unit',
        'harga_kos',
        'harga_jual',
        'stok',
        'stok_minimum',
        'jejak_batch',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'harga_kos' => 'decimal:2',
            'harga_jual' => 'decimal:2',
            'jejak_batch' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    /**
     * Produk yang dicipta dengan stok pembukaan perlu tahu di mana stok itu berada.
     *
     * Tanpa ini, `stok` akan menunjukkan 50 unit sedangkan tiada gudang yang
     * memegangnya — dan penolakan pertama daripada gudang mana pun akan ditolak
     * kerana bakinya sifar. Ia mendarat di gudang lalai, iaitu satu-satunya
     * jawapan yang munasabah apabila borang tidak bertanya.
     */
    protected static function booted(): void
    {
        static::created(function (Product $product) {
            if ((int) $product->stok === 0) {
                return;
            }

            $lokasi = Location::withoutGlobalScopes()
                ->where('workspace_id', $product->workspace_id)
                ->orderByDesc('lalai')
                ->first();

            if ($lokasi === null) {
                return;
            }

            StockBalance::withoutGlobalScopes()->create([
                'workspace_id' => $product->workspace_id,
                'product_id' => $product->id,
                'location_id' => $lokasi->id,
                'kuantiti' => $product->stok,
            ]);
        });
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

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function invoiceScanItems(): HasMany
    {
        return $this->hasMany(InvoiceScanItem::class);
    }

    /**
     * Rekod yang akan hilang bersama produk ini kalau ia dipadam.
     *
     * Setiap kunci asing produk ditanda cascadeOnDelete, jadi memadam satu
     * produk memadam baris daripada tujuh jadual sekali gus — termasuk
     * stock_movements, iaitu jejak audit itu sendiri.
     *
     * @return array<string, int>
     */
    public function kiraanSejarah(): array
    {
        return array_filter([
            'pergerakan' => $this->movements()->count(),
            'jualan' => $this->saleItems()->count(),
            'pesanan' => $this->purchaseOrderItems()->count(),
            'imbasan' => $this->invoiceScanItems()->count(),
            'pemindahan' => $this->transferItems()->count(),
            'lot' => $this->batches()->count(),
        ]);
    }

    /**
     * Produk ini sudah menyentuh rekod lain.
     *
     * Produk begini tidak boleh dipadam — ia diarkibkan. Sistem melindungi
     * label kategori daripada dipadam semasa masih digunakan; jejak audit
     * berhak mendapat sekurang-kurangnya perlindungan yang sama.
     */
    public function adaSejarah(): bool
    {
        return $this->kiraanSejarah() !== [];
    }

    public function transferItems(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
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

    /**
     * Kuantiti yang sudah keluar dari gudang asal tetapi belum diterima di tujuan.
     *
     * Ia masih dikira dalam `stok` kerana barang itu masih milik syarikat —
     * cuma ia tidak berada dalam baki mana-mana gudang.
     */
    public function dalamPerjalanan(): int
    {
        return (int) $this->transferItems()
            ->whereHas('transfer', fn ($q) => $q->where('status', 'dalam_perjalanan'))
            ->sum('kuantiti');
    }

    /**
     * Perbezaan antara jumlah stok dan apa yang dapat dikira lokasinya.
     *
     * Sepatutnya sentiasa sifar: stok = jumlah baki lokasi + stok dalam
     * perjalanan. Nilai bukan sifar bermakna ada aliran yang menyentuh jumlah
     * tanpa menyentuh lokasi, dan itu perlu dilihat, bukan disembunyikan.
     */
    public function bezaLokasi(): int
    {
        return $this->stok - (int) $this->balances()->sum('kuantiti') - $this->dalamPerjalanan();
    }

    /** Jumlah baki merentas semua batch produk ini. */
    public function stokBatch(): int
    {
        return (int) $this->batches()->sum('kuantiti');
    }

    /**
     * Perbezaan antara baki produk dan jumlah baki batchnya.
     *
     * Pelarasan menyeluruh — pelarasan manual dan pengesahan sesi kiraan stok —
     * menetapkan baki produk tanpa menyebut batch, jadi kedua-dua nombor ini
     * boleh terpesong. Perbezaan itu dipaparkan pada halaman produk supaya ia
     * dapat dibetulkan, bukan disembunyikan.
     */
    public function bezaBatch(): int
    {
        return $this->stok - $this->stokBatch();
    }
}
