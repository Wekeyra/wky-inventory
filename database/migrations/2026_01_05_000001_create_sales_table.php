<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Jualan, dan kos barang dijual (COGS).
|
| Setiap baris jualan membekukan DUA harga: harga jual yang dibayar pelanggan,
| dan kos barang itu pada masa ia keluar. Untung kasar ialah perbezaan antara
| kedua-duanya, dan ia mesti dikira daripada angka yang dibekukan pada masa
| jualan — bukan daripada harga produk yang dibaca semula semasa laporan
| dibuka, kerana kedua-dua harga itu boleh berubah selepas jualan berlaku.
|
| Jualan tidak menyimpan jumlahnya sendiri. Jumlah dikira daripada barisnya
| (Sale::jumlahJualan, kosBarangDijual, untungKasar); nombor ringkasan yang
| disimpan berasingan daripada baris yang membentuknya akan terpesong pada saat
| satu baris disunting atau gagal disimpan.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kod', 30);

            // Pelanggan sebagai teks bebas dan bukan jadual sendiri. Modul
            // pelanggan ialah keputusan berasingan; memaksa satu jadual di sini
            // bermakna setiap jualan tunai memerlukan rekod pelanggan dahulu.
            $table->string('pelanggan')->nullable();

            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'kod']);
            $table->index(['workspace_id', 'created_at']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->integer('kuantiti');

            // Harga yang dibayar pelanggan pada masa jualan ini.
            $table->decimal('harga_jual', 12, 2);

            // Kos barang itu pada masa ia keluar. Nullable atas sebab yang sama
            // seperti kos pada pergerakan stok: produk yang harga kosnya belum
            // pernah ditetapkan tidak boleh didakwa percuma, kerana COGS sifar
            // menghasilkan untung kasar yang menyamai keseluruhan jualan.
            $table->decimal('kos_seunit', 12, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
