<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sebab, batch, dan Delivery Order pada pergerakan stok.
 *
 * 'jenis' hanya memberitahu arah pergerakan (masuk, keluar, pelarasan). Sebab
 * memberitahu mengapa — jualan, sampel, kegunaan dalaman, rosak, hilang — dan
 * itulah yang membezakan stok yang menjana wang daripada stok yang lesap.
 * Tanpanya, laporan hanya dapat menunjukkan "keluar 40 unit" tanpa dapat
 * menjawab berapa banyak antaranya benar-benar dijual.
 *
 * Ia disimpan sebagai string dan bukan enum kerana senarai sebab dijangka
 * bertambah; enum memerlukan satu migrasi ubah jadual setiap kali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('sebab', 30)->nullable()->after('jenis');
            $table->foreignId('product_batch_id')->nullable()->after('product_id')
                ->constrained('product_batches')->nullOnDelete();

            // Nombor Delivery Order dijana hanya untuk stok keluar, jadi ia
            // nullable dan bukan sebahagian daripada 'rujukan' yang ditaip
            // pengguna — dokumen yang dicetak perlu nombor yang sistem jamin unik.
            $table->string('no_do', 30)->nullable()->after('rujukan');
            $table->string('penerima')->nullable()->after('no_do');

            $table->unique(['workspace_id', 'no_do']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropUnique(['workspace_id', 'no_do']);
            $table->dropConstrainedForeignId('product_batch_id');
            $table->dropColumn(['sebab', 'no_do', 'penerima']);
        });
    }
};
