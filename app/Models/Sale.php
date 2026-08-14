<?php

namespace App\Models;

use App\Models\Concerns\MilikRuangKerja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory, MilikRuangKerja;

    protected $fillable = [
        'workspace_id',
        'kod',
        'pelanggan',
        'location_id',
        'user_id',
        'catatan',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jumlahUnit(): int
    {
        return (int) $this->items->sum('kuantiti');
    }

    /** Jumlah yang dibayar pelanggan. */
    public function jumlahJualan(): float
    {
        return (float) $this->items->sum(fn (SaleItem $item) => $item->nilaiJualan());
    }

    /**
     * Kos barang dijual.
     *
     * Baris yang kosnya tidak diketahui dikira sebagai sifar di sini, kerana
     * jumlah mesti menghasilkan satu nombor. Gunakan kosPenuh() untuk mengetahui
     * sama ada nombor itu boleh dipercayai.
     */
    public function kosBarangDijual(): float
    {
        return (float) $this->items->sum(fn (SaleItem $item) => $item->nilaiKos() ?? 0);
    }

    public function untungKasar(): float
    {
        return $this->jumlahJualan() - $this->kosBarangDijual();
    }

    /**
     * Setiap baris membawa kosnya.
     *
     * Apabila palsu, COGS dan untung kasar jualan ini kurang daripada yang
     * sebenar — baris tanpa kos menyumbang sifar kepada COGS, jadi untungnya
     * kelihatan lebih besar. Antara muka menandakan jualan begini supaya
     * nombornya tidak dibaca sebagai muktamad.
     */
    public function kosPenuh(): bool
    {
        return $this->items->every(fn (SaleItem $item) => $item->kos_seunit !== null);
    }
}
