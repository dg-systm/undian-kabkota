<?php

use App\Support\GetWinnerProcedure;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Pastikan SP getWinner memakai signature 3 parameter:
     * (id_lokasi_samsat, id_kecamatan, length).
     *
     * Migrasi ini diperlukan untuk database yang sudah menjalankan versi lama
     * (2 parameter) sebelum filter kecamatan ditambahkan.
     */
    public function up(): void
    {
        GetWinnerProcedure::drop();
        GetWinnerProcedure::create();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        GetWinnerProcedure::drop();
    }
};
