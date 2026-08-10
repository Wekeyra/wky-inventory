<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_scans', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 30)->unique();
            $table->enum('status', ['draf', 'selesai', 'dibatalkan', 'gagal'])->default('draf');

            // Maklumat yang dibaca daripada invois oleh AI; semuanya boleh dibetulkan
            // oleh pengguna sebelum pengesahan.
            $table->string('no_invois', 100)->nullable();
            $table->date('tarikh_invois')->nullable();
            $table->string('nama_pembekal')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();

            $table->string('laluan_fail');
            $table->string('nama_fail_asal');
            $table->string('jenis_mime', 100);

            $table->foreignId('dibuka_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disahkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disahkan_pada')->nullable();

            $table->text('ralat')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_scans');
    }
};
