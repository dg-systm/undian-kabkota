<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('draws', function (Blueprint $table) {
            // Penanda undian grand prize (global) vs undian biasa per kecamatan.
            // 'after()' tidak didukung SQL Server, jadi kolom ditambahkan tanpa posisi eksplisit.
            $table->boolean('is_global')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draws', function (Blueprint $table) {
            $table->dropColumn('is_global');
        });
    }
};
