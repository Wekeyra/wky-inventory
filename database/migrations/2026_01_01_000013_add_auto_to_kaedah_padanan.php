<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah kaedah padanan 'auto' untuk produk yang dicipta sendiri oleh sistem
 * daripada baris invois.
 *
 * Ia tidak boleh dikongsi dengan 'manual', kerana 'manual' bermaksud seseorang
 * memilih produk itu dengan matanya sendiri. Baris 'auto' pula belum pernah
 * dilihat sesiapa, jadi ia perlu dapat dibezakan semasa menyemak jejak audit.
 */
return new class extends Migration
{
    private const KAEDAH = ['sku', 'nama', 'manual', 'auto', 'tiada'];

    public function up(): void
    {
        Schema::table('invoice_scan_items', function (Blueprint $table) {
            $table->enum('kaedah_padanan', self::KAEDAH)->default('tiada')->change();
        });
    }

    public function down(): void
    {
        // Baris 'auto' menjadi 'manual' supaya ia tidak melanggar enum lama.
        DB::table('invoice_scan_items')->where('kaedah_padanan', 'auto')->update(['kaedah_padanan' => 'manual']);

        Schema::table('invoice_scan_items', function (Blueprint $table) {
            $table->enum('kaedah_padanan', ['sku', 'nama', 'manual', 'tiada'])->default('tiada')->change();
        });
    }
};
