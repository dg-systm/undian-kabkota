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
            // 'after()' tidak didukung SQL Server, jadi kolom ditambahkan tanpa posisi eksplisit.
            $table->foreignId('prize_category_id')->nullable()->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draws', function (Blueprint $table) {
            $table->dropForeign(['prize_category_id']);
            $table->dropColumn('prize_category_id');
        });
    }
};
