<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained('stock_counts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Baki mengikut rekod sistem pada masa sesi dibuka, disimpan sebagai gambaran tetap.
            $table->integer('kuantiti_rekod');

            // Kekal null sehingga staf memasukkan hasil kiraan fizikal.
            $table->integer('kuantiti_fizikal')->nullable();

            $table->timestamps();

            $table->unique(['stock_count_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_items');
    }
};
