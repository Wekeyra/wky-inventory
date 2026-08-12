<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Akaun Google tidak mempunyai kata laluan tempatan.
            $table->string('password')->nullable()->change();

            $table->string('google_id')->nullable()->unique()->after('email');
            $table->enum('status', ['menunggu', 'aktif', 'ditolak'])->default('menunggu')->after('peranan');
        });

        // Semua akaun sedia ada dicipta oleh admin, jadi ia terus aktif.
        DB::table('users')->update(['status' => 'aktif']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};
