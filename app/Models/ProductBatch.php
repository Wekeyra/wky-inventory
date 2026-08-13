<?php

namespace App\Models;

use App\Models\Concerns\MilikRuangKerja;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ProductBatch extends Model
{
    use HasFactory, MilikRuangKerja;

    /** Bilangan hari sebelum tarikh luput yang dikira sebagai "hampir tamat tempoh". */
    public const HARI_AMARAN = 30;

    protected $fillable = [
        'workspace_id',
        'product_id',
        'no_batch',
        'no_siri',
        'tarikh_luput',
        'kuantiti',
    ];

    protected function casts(): array
    {
        return [
            'tarikh_luput' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** Batch yang masih ada baki; batch kosong tidak perlu dipilih mahupun diberi amaran. */
    public function scopeAdaBaki(Builder $query): Builder
    {
        return $query->where('kuantiti', '>', 0);
    }

    /**
     * Batch yang sudah luput atau akan luput dalam tempoh amaran.
     *
     * Batch tanpa tarikh luput dikecualikan: produk yang tidak tamat tempoh
     * tetap boleh dijejak batchnya untuk tujuan panggil balik (recall).
     */
    public function scopeHampirLuput(Builder $query, int $hari = self::HARI_AMARAN): Builder
    {
        return $query->whereNotNull('tarikh_luput')
            ->whereDate('tarikh_luput', '<=', Carbon::today()->addDays($hari));
    }

    public function sudahLuput(): bool
    {
        return $this->tarikh_luput !== null && $this->tarikh_luput->isPast();
    }

    /** Bilangan hari sehingga luput; negatif bermakna sudah terlepas tarikhnya. */
    public function hariLagi(): ?int
    {
        return $this->tarikh_luput === null
            ? null
            : (int) Carbon::today()->diffInDays($this->tarikh_luput, false);
    }

    /** Nama kelas penuh supaya Tailwind dapat mengesannya semasa membina CSS. */
    public function kelasLuput(): string
    {
        $hari = $this->hariLagi();

        return match (true) {
            $hari === null => 'lencana-kelabu',
            $hari < 0 => 'lencana-merah',
            $hari <= self::HARI_AMARAN => 'lencana-kuning',
            default => 'lencana-hijau',
        };
    }

    public function labelLuput(): string
    {
        $hari = $this->hariLagi();

        return match (true) {
            $hari === null => __('wky.umum.kosong'),
            $hari < 0 => __('wky.batch.luput_lepas', ['hari' => abs($hari)]),
            $hari === 0 => __('wky.batch.luput_hari_ini'),
            default => __('wky.batch.luput_lagi', ['hari' => $hari]),
        };
    }
}
