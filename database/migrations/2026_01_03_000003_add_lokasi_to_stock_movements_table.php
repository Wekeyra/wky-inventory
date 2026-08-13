<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lokasi pada setiap pergerakan stok, dan jenis baharu 'pindah'.
 *
 * Pemindahan tidak boleh direkod sebagai pasangan masuk/keluar: jumlah stok
 * syarikat tidak berubah apabila barang berpindah rak, dan laporan bulanan
 * mengira 'masuk' dan 'keluar' sebagai kemasukan dan pengeluaran sebenar.
 * Sepasang baris palsu akan menunjukkan pembelian dan jualan yang tidak pernah
 * berlaku. Jenis tersendiri membuatkannya terkecuali daripada kiraan itu
 * dengan sendirinya.
 */
return new class extends Migration
{
    private const JENIS = ['masuk', 'keluar', 'pelarasan', 'pindah'];

    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->enum('jenis', self::JENIS)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('product_batch_id')
                ->constrained('locations')->nullOnDelete();
            // Hanya diisi oleh pemindahan: lokasi yang menerima barang itu.
            $table->foreignId('location_tujuan_id')->nullable()->after('location_id')
                ->constrained('locations')->nullOnDelete();
        });

        // Pergerakan lama berlaku sebelum lokasi wujud, jadi ia dinisbahkan
        // kepada gudang lalai — di situlah stoknya memang berada selama ini.
        foreach (DB::table('locations')->where('lalai', true)->get() as $lokasi) {
            DB::table('stock_movements')
                ->where('workspace_id', $lokasi->workspace_id)
                ->whereNull('location_id')
                ->update(['location_id' => $lokasi->id]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_tujuan_id');
            $table->dropConstrainedForeignId('location_id');
        });

        DB::table('stock_movements')->where('jenis', 'pindah')->delete();

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->enum('jenis', ['masuk', 'keluar', 'pelarasan'])->change();
        });
    }
};
