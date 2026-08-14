<?php

namespace App\Services\Storan;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Cakera untuk fail yang dimuat naik pengguna: gambar invois dan gambar produk.
 *
 * Nama cakera datang daripada konfigurasi dan bukan ditulis tetap pada setiap
 * pemanggil. Lalainya `local`, iaitu `storage/app/private` pada pelayan itu
 * sendiri — dan di situlah bahayanya.
 *
 * Pada hos berkontena, cakera tempatan selalunya **sementara**: ia dibina
 * semula pada setiap deploy. Rekod pangkalan data kekal, tetapi gambarnya
 * lenyap, dan tiada apa dalam sistem yang memberitahu ia telah hilang sehingga
 * seseorang cuba membuka invois lama.
 *
 * Menukar ke storan kekal ialah satu pemboleh ubah persekitaran:
 *
 *     MUAT_NAIK_DISK=s3
 *
 * Tiada kod perlu disentuh. Sebab itu ia berada di sini dan bukan disebar
 * sebagai Storage::disk('local') pada sepuluh tempat berlainan — sepuluh tempat
 * bermakna satu daripadanya akan tertinggal semasa penukaran, dan fail yang
 * ditulis ke satu cakera tetapi dibaca daripada cakera lain gagal secara senyap.
 */
class Muatnaik
{
    public static function cakera(): Filesystem
    {
        return Storage::disk(self::nama());
    }

    /** Nama cakera semasa — berguna untuk paparan diagnostik. */
    public static function nama(): string
    {
        return config('filesystems.muat_naik');
    }

    /** Benar apabila fail disimpan pada cakera pelayan itu sendiri. */
    public static function tempatan(): bool
    {
        return in_array(self::nama(), ['local', 'public'], true);
    }
}
