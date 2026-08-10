<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_scan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_scan_id')->constrained('invoice_scans')->cascadeOnDelete();

            // Null bermakna baris ini belum dipadankan dengan mana-mana produk.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            // Teks mentah seperti yang dibaca daripada invois, disimpan supaya
            // pengguna boleh membandingkan padanan dengan dokumen asal.
            $table->string('sku_invois', 100)->nullable();
            $table->string('nama_invois');

            $table->integer('kuantiti');
            $table->decimal('harga_unit', 12, 2)->nullable();

            $table->enum('kaedah_padanan', ['sku', 'nama', 'manual', 'tiada'])->default('tiada');
            $table->boolean('dilangkau')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_scan_items');
    }
};
