<?php

namespace App\Models;

use App\Models\Concerns\MilikRuangKerja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory, MilikRuangKerja;

    /**
     * Perubahan status yang dibenarkan.
     *
     * Ditulis sebagai peta dan bukan disebar sebagai `if` di dalam pengawal,
     * supaya satu-satunya jawapan tentang "bolehkah PO ini bergerak ke sana"
     * berada di satu tempat. Status akhir — ditolak, selesai, dibatalkan —
     * sengaja tiada laluan keluar: PO yang sudah diputuskan tidak boleh
     * dihidupkan semula, kerana rekod yang boleh berubah selepas diluluskan
     * bukan lagi kelulusan.
     *
     * @var array<string, list<string>>
     */
    public const PERALIHAN = [
        'draf' => ['menunggu', 'dibatalkan'],
        'menunggu' => ['diluluskan', 'ditolak', 'draf'],
        'diluluskan' => ['selesai', 'dibatalkan'],
        'ditolak' => [],
        'selesai' => [],
        'dibatalkan' => [],
    ];

    protected $fillable = [
        'workspace_id',
        'kod',
        'status',
        'supplier_id',
        'dipohon_oleh',
        'diputuskan_oleh',
        'diputuskan_pada',
        'sebab_tolak',
        'tarikh_diperlukan',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'diputuskan_pada' => 'datetime',
            'tarikh_diperlukan' => 'date',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dipohon_oleh');
    }

    public function pemutus(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diputuskan_oleh');
    }

    public function bolehKe(string $status): bool
    {
        return in_array($status, self::PERALIHAN[$this->status] ?? [], true);
    }

    /** Hanya draf yang boleh disunting; selepas dihantar, isinya ialah apa yang diluluskan. */
    public function bolehDisunting(): bool
    {
        return $this->status === 'draf';
    }

    public function bolehTerima(): bool
    {
        return $this->status === 'diluluskan';
    }

    public function jumlahUnit(): int
    {
        return (int) $this->items->sum('kuantiti');
    }

    public function jumlahDiterima(): int
    {
        return (int) $this->items->sum('kuantiti_diterima');
    }

    /** Nilai PO pada kos yang dipersetujui; baris tanpa kos dikira sebagai sifar. */
    public function jumlahNilai(): float
    {
        return (float) $this->items->sum(fn (PurchaseOrderItem $item) => $item->nilai() ?? 0);
    }

    /**
     * Semua baris sudah diterima sepenuhnya.
     *
     * Dikira dan bukan disimpan: status penerimaan yang disimpan berasingan
     * daripada angka yang membentuknya akan terpesong pada saat satu penerimaan
     * gagal separuh jalan.
     */
    public function penerimaanSelesai(): bool
    {
        return $this->items->every(fn (PurchaseOrderItem $item) => $item->bakiTerima() <= 0);
    }

    /** Sudah ada barang diterima, tetapi belum semuanya. */
    public function diterimaSepara(): bool
    {
        return $this->jumlahDiterima() > 0 && ! $this->penerimaanSelesai();
    }

    public function labelStatus(): string
    {
        if ($this->status === 'diluluskan' && $this->diterimaSepara()) {
            return __('wky.po.status_separa');
        }

        return __('wky.po.status_'.$this->status);
    }

    /** Nama kelas penuh supaya Tailwind dapat mengesannya semasa membina CSS. */
    public function kelasStatus(): string
    {
        if ($this->status === 'diluluskan' && $this->diterimaSepara()) {
            return 'lencana-kuning';
        }

        return match ($this->status) {
            'draf' => 'lencana-kelabu',
            'menunggu' => 'lencana-kuning',
            'diluluskan' => 'lencana-biru',
            'selesai' => 'lencana-hijau',
            'ditolak', 'dibatalkan' => 'lencana-bahaya',
            default => 'lencana-kelabu',
        };
    }
}
