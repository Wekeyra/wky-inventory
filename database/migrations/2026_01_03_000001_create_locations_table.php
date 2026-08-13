<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gudang dan cawangan.
 *
 * Setiap ruang kerja mendapat satu lokasi lalai supaya data sedia ada mempunyai
 * tempat untuk didudukkan, dan supaya syarikat yang hanya ada satu premis tidak
 * pernah perlu memikirkan modul ini — borang memilih lokasi lalai dengan
 * sendirinya apabila hanya ada satu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kod', 30);
            $table->string('nama');
            $table->text('alamat')->nullable();
            // Lokasi lalai menerima stok apabila borang tidak menyebut lokasi —
            // termasuk pengesahan imbasan invois dan data yang dipindahkan ke sini.
            $table->boolean('lalai')->default(false);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['workspace_id', 'kod']);
        });

        // Setiap ruang kerja sedia ada mendapat gudang utamanya.
        foreach (DB::table('workspaces')->pluck('id') as $ruangKerja) {
            DB::table('locations')->insert([
                'workspace_id' => $ruangKerja,
                'kod' => 'UTAMA',
                'nama' => 'Gudang Utama',
                'lalai' => true,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
