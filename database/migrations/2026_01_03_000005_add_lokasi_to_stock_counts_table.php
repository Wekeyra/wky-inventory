<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sesi kiraan stok terikat pada satu lokasi.
 *
 * Kiraan fizikal ialah perbuatan berdiri di satu gudang dan membilang apa yang
 * ada di rak situ. Sesi yang merangkumi semua gudang sekali gus tidak boleh
 * dilakukan oleh sesiapa, dan pelarasannya tidak dapat memberitahu gudang mana
 * yang sebenarnya kurang.
 *
 * `kuantiti_rekod` pada baris sesi kini bermaksud baki lokasi itu, bukan baki
 * keseluruhan produk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_counts', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('category_id')
                ->constrained('locations')->nullOnDelete();
        });

        foreach (DB::table('locations')->where('lalai', true)->get() as $lokasi) {
            DB::table('stock_counts')
                ->where('workspace_id', $lokasi->workspace_id)
                ->whereNull('location_id')
                ->update(['location_id' => $lokasi->id]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_counts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });
    }
};
