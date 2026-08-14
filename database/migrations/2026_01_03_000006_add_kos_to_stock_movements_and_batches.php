<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Kos seunit pada masa sesuatu pergerakan berlaku.
|
| Sebelum ini satu-satunya kos dalam sistem ialah products.harga_kos — satu
| nilai semasa yang ditulis ganti setiap kali produk dikemas kini. Nilai stok
| dan laporan dikira daripadanya, jadi menaikkan harga pembekal hari ini turut
| menukar nilai laporan bulan lepas, sedangkan stok itu dibeli pada harga lama.
|
| Lajur ini dibiarkan NULL dan bukan 0 secara lalai. Baris sedia ada memang
| tiada kos yang direkod, dan 0 akan mendakwa barang itu diterima percuma —
| satu pembohongan yang akan mengalir terus ke dalam setiap laporan yang
| menjumlahkannya. NULL bermaksud "tidak direkod", yang memang benar, dan kos
| itu tidak boleh dibina semula kemudian.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('kos_seunit', 12, 2)->nullable()->after('kuantiti');
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->decimal('kos_seunit', 12, 2)->nullable()->after('kuantiti');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn('kos_seunit');
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropColumn('kos_seunit');
        });
    }
};
