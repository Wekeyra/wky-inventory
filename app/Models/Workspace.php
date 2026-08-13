<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ruang kerja sebuah syarikat. Semua inventori, pembekal, pergerakan stok dan
 * pengguna dimiliki oleh satu ruang kerja, dan tidak pernah bertemu data ruang
 * kerja lain.
 */
class Workspace extends Model
{
    use HasFactory;

    protected $fillable = ['nama'];

    /**
     * Setiap ruang kerja baharu bermula dengan satu gudang.
     *
     * Ia dicipta di sini dan bukan dalam controller pendaftaran, kerana ruang
     * kerja juga dicipta oleh arahan konsol dan ujian — dan aliran yang tidak
     * bertanya lokasi (pengesahan imbasan invois, contohnya) mengandaikan
     * lokasi lalai sentiasa ada.
     */
    protected static function booted(): void
    {
        static::created(function (Workspace $ruangKerja) {
            Location::withoutGlobalScopes()->create([
                'workspace_id' => $ruangKerja->id,
                'kod' => 'UTAMA',
                'nama' => 'Gudang Utama',
                'lalai' => true,
                'aktif' => true,
            ]);
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}
