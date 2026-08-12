<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membezakan imbasan yang sudah dibaca AI daripada yang baru disimpan sebagai
 * gambar sahaja. Tanpa penanda ini, "draf tanpa baris" boleh bermakna dua
 * perkara berbeza dan antara muka tidak dapat menentukan yang mana satu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_scans', function (Blueprint $table) {
            $table->timestamp('dibaca_pada')->nullable()->after('dibuka_oleh');
        });

        // Setiap imbasan sedia ada memang datang daripada bacaan AI.
        DB::table('invoice_scans')->update(['dibaca_pada' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('invoice_scans', function (Blueprint $table) {
            $table->dropColumn('dibaca_pada');
        });
    }
};
