<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Barcode, gambar, dan pilihan menjejak batch pada produk.
 *
 * Barcode diasingkan daripada SKU kerana kedua-duanya menjawab soalan berbeza:
 * SKU ialah kod dalaman yang dipilih sendiri, manakala barcode ialah kod yang
 * sudah tercetak pada bungkusan oleh pengilang. Satu produk boleh mempunyai
 * kedua-duanya, dan pengimbas di kaunter hanya tahu yang kedua.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode', 100)->nullable()->after('sku');
            $table->string('laluan_gambar')->nullable()->after('keterangan');

            // Kebanyakan produk SME tidak berbatch (skru, kabel, alat tulis).
            // Menjejaknya untuk semua produk hanya menambah dua medan wajib pada
            // setiap kemasukan stok, jadi ia dihidupkan produk demi produk.
            $table->boolean('jejak_batch')->default(false)->after('stok_minimum');

            // Berskop ruang kerja seperti SKU: dua syarikat berlainan boleh
            // menjual produk yang sama, jadi barcode yang sama akan bertembung.
            $table->unique(['workspace_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['workspace_id', 'barcode']);
            $table->dropColumn(['barcode', 'laluan_gambar', 'jejak_batch']);
        });
    }
};
