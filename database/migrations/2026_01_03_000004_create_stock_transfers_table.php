<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pemindahan stok antara lokasi, berserta peringkat "dalam perjalanan".
 *
 * Barang yang sudah keluar dari gudang A tetapi belum sampai ke gudang B tidak
 * berada di mana-mana lokasi. Tanpa peringkat ini, sistem terpaksa memilih
 * antara dua pembohongan: barang itu masih di A (sedangkan lori sudah bertolak)
 * atau sudah di B (sedangkan tiada sesiapa lagi menerimanya). Kuantiti dalam
 * perjalanan kekal dikira dalam jumlah stok syarikat, cuma tidak dalam baki
 * mana-mana gudang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kod', 30);
            $table->enum('status', ['dalam_perjalanan', 'selesai', 'dibatalkan'])->default('dalam_perjalanan');
            $table->foreignId('location_asal_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('location_tujuan_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('dihantar_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diterima_pada')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'kod']);
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('kuantiti');
            $table->timestamps();

            $table->unique(['stock_transfer_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
