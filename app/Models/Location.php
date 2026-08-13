<?php

namespace App\Models;

use App\Models\Concerns\MilikRuangKerja;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory, MilikRuangKerja;

    protected $fillable = [
        'workspace_id',
        'kod',
        'nama',
        'alamat',
        'lalai',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'lalai' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /**
     * Lokasi yang menerima stok apabila tiada lokasi disebut.
     *
     * Pengesahan imbasan invois dan mana-mana aliran lain yang tidak bertanya
     * lokasi mendarat di sini. Ia tidak pernah kosong: migrasi mencipta satu
     * untuk setiap ruang kerja, dan lokasi lalai tidak boleh dipadam.
     */
    public static function lalai(): ?self
    {
        return static::query()->where('lalai', true)->first()
            ?? static::query()->aktif()->orderBy('id')->first();
    }

    /** Jumlah unit yang sedang berada di lokasi ini, merentas semua produk. */
    public function jumlahUnit(): int
    {
        return (int) $this->balances()->sum('kuantiti');
    }

    public function adaStok(): bool
    {
        return $this->balances()->where('kuantiti', '>', 0)->exists();
    }
}
