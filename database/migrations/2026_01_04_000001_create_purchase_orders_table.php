<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Permohonan pembelian dan Purchase Order — dua langkah pertama aliran
| perolehan, disimpan sebagai satu rekod dan bukan dua.
|
| Permohonan yang diluluskan *menjadi* PO; ia bukan dokumen berasingan yang
| disalin daripada permohonan. Menyalinnya bermakna ada dua tempat kebenaran
| tentang barang yang sama, dan yang kedua akan terpesong daripada yang pertama
| sebaik sahaja seseorang menyunting salah satunya.
|
| Status penerimaan tidak disimpan di sini. Ia dikira daripada kuantiti_diterima
| pada setiap baris (lihat PurchaseOrder::penerimaanSelesai), kerana status yang
| disimpan berasingan daripada angka yang membentuknya akan terpesong pada saat
| satu penerimaan gagal separuh jalan.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kod', 30);
            $table->enum('status', [
                'draf',
                'menunggu',
                'diluluskan',
                'ditolak',
                'selesai',
                'dibatalkan',
            ])->default('draf');

            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();

            $table->foreignId('dipohon_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diputuskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diputuskan_pada')->nullable();
            $table->text('sebab_tolak')->nullable();

            $table->date('tarikh_diperlukan')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Kod berjujukan dalam ruang kerja sahaja, sama seperti SKU dan
            // nombor DO: dua syarikat bebas mempunyai PO-2026-001 masing-masing.
            $table->unique(['workspace_id', 'kod']);
            $table->index(['workspace_id', 'status']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('kuantiti');

            // Kos yang dipersetujui semasa PO dibuat. Inilah kos yang dicap pada
            // pergerakan stok semasa barang diterima, jadi harga yang diluluskan
            // ialah harga yang masuk ke dalam kira-kira — bukan harga kos produk
            // yang mungkin sudah berubah antara kelulusan dan penghantaran.
            $table->decimal('kos_seunit', 12, 2)->nullable();

            // Penerimaan separa: satu PO boleh diterima berkali-kali sehingga
            // kuantiti_diterima mencecah kuantiti.
            $table->integer('kuantiti_diterima')->default(0);

            $table->timestamps();
            $table->unique(['purchase_order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
