<?php

use App\Models\Workspace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Ciri lanjutan yang dihidupkan bagi setiap ruang kerja.
|
| Sistem ini tumbuh melepasi MVP jauh lebih cepat daripada pengguna barunya.
| Ruang kerja baharu kini bermula dengan lapan ciri asas sahaja — produk, stok
| masuk/keluar, baki, amaran stok rendah, pelarasan, laporan dan jejak audit —
| dan modul lanjutan dibuka satu demi satu apabila syarikat itu benar-benar
| memerlukannya.
|
| Tiada kod dibuang. Modul itu semuanya kekal; ia cuma tidak lagi ditayangkan
| kepada syarikat yang baru sahaja memasukkan produk pertamanya.
|
| Ruang kerja SEDIA ADA mendapat semua ciri dihidupkan. Mereka sudah pun
| menggunakan modul itu dan sudah ada datanya; mematikannya di sini akan
| menyembunyikan kerja yang sudah dibuat, dan itu bukan naik taraf — itu
| kehilangan.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->json('ciri')->nullable()->after('nama');
        });

        // Tanpa skop global: migrasi berjalan tanpa pengguna log masuk, dan
        // setiap ruang kerja sedia ada perlu disentuh.
        DB::table('workspaces')->update(['ciri' => json_encode(Workspace::CIRI)]);
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn('ciri');
        });
    }
};
