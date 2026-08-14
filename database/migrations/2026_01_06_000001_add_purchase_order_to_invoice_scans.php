<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Memautkan imbasan invois kepada pesanan belian yang dibayarnya.
|
| Sebelum ini kedua-dua aliran merekod stok masuk secara berasingan: PO tahu
| berapa yang dipesan, imbasan invois tahu berapa yang sampai, dan tiada apa
| yang menyambungkan kedua-duanya. Pesanan kekal "diluluskan" selama-lamanya
| walaupun barangnya sudah lama tiba melalui imbasan.
|
| Nullable kerana kebanyakan invois memang tiada PO — pembelian runcit, dan
| setiap invois yang diimbas sebelum modul PO wujud.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_scans', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')->nullable()->after('supplier_id')
                ->constrained('purchase_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_scans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_order_id');
        });
    }
};
