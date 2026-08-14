<?php

namespace App\Models;

use App\Models\Concerns\MilikRuangKerja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory, MilikRuangKerja;

    /**
     * Sebab yang dibenarkan bagi setiap jenis pergerakan.
     *
     * Sebab dikunci mengikut jenis kerana gabungan yang tidak masuk akal —
     * "stok masuk kerana jualan" — akan merosakkan laporan yang mengira jualan
     * daripada medan ini. Senarai ini ialah satu-satunya sumber kebenaran:
     * borang membina pilihannya daripadanya dan controller mengesahkan
     * terhadapnya, jadi kedua-duanya tidak boleh terpesong.
     */
    public const SEBAB = [
        'masuk' => ['pembelian', 'pemulangan_pelanggan', 'lain_lain'],
        'keluar' => ['jualan', 'sampel', 'kegunaan_dalaman', 'rosak', 'hilang', 'pemulangan_pembekal', 'lain_lain'],
        'pelarasan' => ['kiraan_fizikal', 'rosak', 'hilang', 'lain_lain'],
        // 'pindah' tiada dalam borang: ia dijana oleh modul Pemindahan Stok
        // sahaja, kerana memindahkan barang memerlukan dua gudang dan dua
        // peringkat, bukan satu baris borang.
        'pindah' => ['pindah_hantar', 'pindah_terima', 'pindah_batal'],
    ];

    protected $fillable = [
        'workspace_id',
        'product_id',
        'product_batch_id',
        'location_id',
        'location_tujuan_id',
        'user_id',
        'jenis',
        'sebab',
        'kuantiti',
        'kos_seunit',
        'stok_sebelum',
        'stok_selepas',
        'rujukan',
        'no_do',
        'penerima',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'kos_seunit' => 'decimal:2',
        ];
    }

    /**
     * Nilai kos keseluruhan pergerakan ini, atau null kalau kosnya tidak direkod.
     *
     * Dipulangkan sebagai null dan bukan 0 supaya laporan boleh membezakan
     * "pergerakan bernilai sifar" daripada "pergerakan yang berlaku sebelum kos
     * mula direkod". Kedua-duanya menjumlah kepada 0, tetapi hanya satu
     * daripadanya benar.
     */
    public function nilaiKos(): ?float
    {
        return $this->kos_seunit === null
            ? null
            : (float) $this->kos_seunit * $this->kuantiti;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_tujuan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function labelJenis(): string
    {
        return __('wky.stok.' . ($this->jenis === 'pelarasan' ? 'pelarasan' : $this->jenis));
    }

    /** Pemindahan tidak mengubah jumlah stok syarikat, hanya gudang tempatnya berada. */
    public function isPindah(): bool
    {
        return $this->jenis === 'pindah';
    }

    /** Nama kelas penuh supaya Tailwind dapat mengesannya semasa membina CSS. */
    public function kelasJenis(): string
    {
        return match ($this->jenis) {
            'masuk' => 'lencana-hijau',
            'keluar' => 'lencana-bahaya',
            'pindah' => 'lencana-biru',
            default => 'lencana-kuning',
        };
    }

    public function labelSebab(): ?string
    {
        return $this->sebab === null ? null : __('wky.sebab.' . $this->sebab);
    }

    /** Delivery Order hanya wujud untuk stok keluar; tiada barang keluar, tiada dokumen. */
    public function adaDeliveryOrder(): bool
    {
        return $this->jenis === 'keluar' && $this->no_do !== null;
    }
}
