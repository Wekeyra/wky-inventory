<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baki mengikut nombor batch bagi produk yang menjejaknya.
 *
 * Batch disimpan sebagai baris berasingan dan bukan sebagai medan pada
 * pergerakan stok, kerana soalan yang perlu dijawab ialah "berapa banyak lot
 * ini masih ada dan bila ia luput" — soalan tentang baki semasa, bukan tentang
 * satu transaksi lampau. Pergerakan tetap merujuk baris ini supaya jejak audit
 * menunjukkan lot mana yang bergerak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('no_batch', 100);
            $table->string('no_siri', 100)->nullable();
            $table->date('tarikh_luput')->nullable();
            $table->integer('kuantiti')->default(0);
            $table->timestamps();

            // Satu nombor batch bagi satu produk. Kemasukan kedua bagi batch yang
            // sama menambah kuantiti pada baris sedia ada, bukan mencipta baris
            // kembar yang memecahkan baki lot itu kepada dua.
            $table->unique(['product_id', 'no_batch']);

            // Amaran "hampir tamat tempoh" mengimbas mengikut tarikh luput.
            $table->index(['workspace_id', 'tarikh_luput']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
