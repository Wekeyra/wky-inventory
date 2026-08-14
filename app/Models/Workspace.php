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

    /**
     * Modul lanjutan yang boleh dihidupkan, mengikut turutan ia biasanya
     * diperlukan.
     *
     * Yang TIADA dalam senarai ini ialah teras yang sentiasa hidup: produk,
     * kategori, pembekal, pergerakan stok, kiraan stok, laporan bulanan dan
     * pengguna. Itulah lapan fungsi minimum sebuah sistem inventori, dan
     * mematikan mana-mana daripadanya bermakna sistem itu tidak lagi berfungsi
     * sebagai sistem inventori.
     *
     * @var list<string>
     */
    public const CIRI = ['gudang', 'imbas', 'po', 'jualan', 'analitik'];

    protected $fillable = ['nama', 'ciri'];

    protected function casts(): array
    {
        return ['ciri' => 'array'];
    }

    /**
     * Ciri lanjutan yang dihidupkan untuk ruang kerja ini.
     *
     * Nilai disaring terhadap CIRI supaya nama yang sudah dibuang daripada
     * sistem — atau yang tersalah tulis ke dalam pangkalan data — tidak boleh
     * membuka apa-apa.
     *
     * @return list<string>
     */
    public function ciriAktif(): array
    {
        return array_values(array_intersect($this->ciri ?? [], self::CIRI));
    }

    public function adaCiri(string $ciri): bool
    {
        return in_array($ciri, $this->ciriAktif(), true);
    }

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
        /*
         | Ruang kerja yang dicipta tanpa menyatakan cirinya — ujian, seeder,
         | arahan konsol — mendapat semuanya. Hanya pendaftaran syarikat baharu
         | yang sengaja bermula dengan MVP sahaja, dan ia menyatakannya secara
         | eksplisit.
         |
         | Keputusan "mula ringkas" itu milik saat sebuah syarikat mendaftar,
         | bukan milik setiap baris dalam jadual ini. Meletakkannya di sini
         | bermakna setiap ruang kerja yang dicipta melalui jalan lain akan
         | senyap-senyap kehilangan modulnya.
         |
         | Perbandingan dengan null dan bukan blank(): array kosong ialah
         | pilihan yang sah — ia bermaksud "MVP sahaja".
         */
        static::creating(function (Workspace $ruangKerja) {
            if ($ruangKerja->ciri === null) {
                $ruangKerja->ciri = self::CIRI;
            }
        });

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
