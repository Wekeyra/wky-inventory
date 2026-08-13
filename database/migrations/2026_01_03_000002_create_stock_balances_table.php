<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Baki setiap produk pada setiap lokasi.
 *
 * `products.stok` dikekalkan sebagai jumlah keseluruhan merentas semua lokasi,
 * bukan digantikan. Setiap halaman, laporan dan amaran stok rendah yang sedia
 * ada bergantung padanya, dan angka "berapa banyak barang ini ada semuanya"
 * memang soalan yang paling kerap ditanya. Jadual ini menjawab soalan kedua:
 * di mana barang itu berada.
 *
 * Hubungannya: products.stok = jumlah baris di sini + stok dalam perjalanan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            // Rak atau bin dalam gudang itu. Ia catatan tempat dan bukan struktur
            // berasingan, kerana rak berubah lebih kerap daripada gudang dan
            // menjadikannya jadual sendiri bermakna satu lagi modul untuk diurus.
            $table->string('rak', 50)->nullable();
            $table->integer('kuantiti')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'location_id']);
            $table->index(['workspace_id', 'location_id']);
        });

        // Stok sedia ada diletakkan pada lokasi lalai setiap ruang kerja.
        $lalai = DB::table('locations')->where('lalai', true)->pluck('id', 'workspace_id');

        DB::table('products')->orderBy('id')->chunk(200, function ($produk) use ($lalai) {
            foreach ($produk as $satu) {
                if (! isset($lalai[$satu->workspace_id])) {
                    continue;
                }

                DB::table('stock_balances')->insert([
                    'workspace_id' => $satu->workspace_id,
                    'product_id' => $satu->id,
                    'location_id' => $lalai[$satu->workspace_id],
                    'kuantiti' => $satu->stok,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
