<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengasingkan data mengikut ruang kerja supaya setiap syarikat yang mendaftar
 * bermula dengan inventori kosong dan tidak nampak data syarikat lain.
 *
 * Semua data sedia ada dipindahkan ke satu ruang kerja lalai milik akaun asal.
 */
return new class extends Migration
{
    /**
     * Jadual yang datanya dimiliki oleh sesebuah ruang kerja. Baris anak seperti
     * stock_count_items tidak disenaraikan kerana ia hanya dicapai melalui
     * induknya, yang sudah pun berskop.
     */
    private const JADUAL = [
        'categories',
        'suppliers',
        'products',
        'stock_movements',
        'stock_counts',
        'invoice_scans',
    ];

    /**
     * Kod yang perlu unik dalam ruang kerja masing-masing, bukan merentas
     * seluruh sistem — dua syarikat berlainan boleh guna SKU yang sama.
     */
    private const UNIK = [
        'categories' => 'kod',
        'suppliers' => 'kod',
        'products' => 'sku',
        'stock_counts' => 'kod',
        'invoice_scans' => 'kod',
    ];

    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        foreach (self::JADUAL as $jadual) {
            Schema::table($jadual, function (Blueprint $table) {
                $table->foreignId('workspace_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        // Ruang kerja lalai hanya dicipta jika sudah ada data untuk dipindahkan.
        if (DB::table('users')->exists()) {
            $lalai = DB::table('workspaces')->insertGetId([
                'nama' => 'Wekeyra',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->update(['workspace_id' => $lalai]);

            foreach (self::JADUAL as $jadual) {
                DB::table($jadual)->update(['workspace_id' => $lalai]);
            }
        }

        foreach (self::UNIK as $jadual => $lajur) {
            Schema::table($jadual, function (Blueprint $table) use ($jadual, $lajur) {
                $table->dropUnique("{$jadual}_{$lajur}_unique");
                $table->unique(['workspace_id', $lajur]);
            });
        }
    }

    public function down(): void
    {
        foreach (self::UNIK as $jadual => $lajur) {
            Schema::table($jadual, function (Blueprint $table) use ($lajur) {
                $table->dropUnique(['workspace_id', $lajur]);
                $table->unique($lajur);
            });
        }

        foreach ([...self::JADUAL, 'users'] as $jadual) {
            Schema::table($jadual, function (Blueprint $table) {
                $table->dropConstrainedForeignId('workspace_id');
            });
        }

        Schema::dropIfExists('workspaces');
    }
};
