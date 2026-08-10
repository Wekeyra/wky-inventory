<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'kod',
        'status',
        'no_invois',
        'tarikh_invois',
        'nama_pembekal',
        'supplier_id',
        'laluan_fail',
        'nama_fail_asal',
        'jenis_mime',
        'dibuka_oleh',
        'disahkan_oleh',
        'disahkan_pada',
        'ralat',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tarikh_invois' => 'date',
            'disahkan_pada' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceScanItem::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
        return __('wky.imbas.status_' . $this->status);
    }

    /** Nama kelas penuh supaya Tailwind dapat mengesannya semasa membina CSS. */
    public function kelasStatus(): string
    {
        return match ($this->status) {
            'draf' => 'lencana-kuning',
            'selesai' => 'lencana-hijau',
            'gagal' => 'lencana-merah',
            default => 'lencana-kelabu',
        };
    }

    /** Rujukan yang dicap pada setiap pergerakan stok yang dijana sesi ini. */
    public function rujukanStok(): string
    {
        return $this->no_invois ?: $this->kod;
    }
}
