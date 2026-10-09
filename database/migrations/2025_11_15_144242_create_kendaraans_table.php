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
        if (!Schema::hasTable('kendaraans')) {
            Schema::create('kendaraans', function (Blueprint $table) {
                $table->id();
                $table->string('id_kendaraan');
                $table->string('no_polisi', 15);
                $table->string('nama');
                $table->string('alamat');
                $table->string('id_kecamatan')->nullable();
                $table->string('id_kelurahan')->nullable();
                $table->integer('id_lokasi');
                $table->string('lokasi');
                $table->integer('id_lokasi_proses');
                $table->string('lokasi_proses');
                $table->string('id_billing');
                $table->integer('id_warna_tnkb');
                $table->string('warna_tnkb');
                $table->integer('id_fungsi_kend');
                $table->string('fungsi_kend');
                $table->integer('roda');
                $table->integer('id_pendaftaran');
                $table->string('pendaftaran');
                $table->date('tgl_daftar');
                $table->date('tgl_bayar');
                $table->date('tgl_jatuh_tempo');
                $table->date('tgl_akhir_stnk');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('kendaraans');
    }
};
