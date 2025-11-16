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
        Schema::create('penilaian_slhd_parsed', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penilaian_slhd_id')->constrained('penilaian_slhd')->onDelete('cascade');
            $table->foreignId('id_dinas')->constrained('dinas')->onDelete('cascade')->nullable();
            $table->string('nama_dinas')->default('tidak diketahui')->nullable();
            $table->unsignedTinyInteger('Bab_1')->nullable();
            $table->unsignedTinyInteger('Jumlah_Pemanfaatan_Pelayanan_Laboratorium')->nullable();
            $table->unsignedTinyInteger('Daya_Dukung_dan_Daya_Tampung_Lingkungan_Hidup')->nullable();
            $table->unsignedTinyInteger('Kajian_Lingkungan_Hidup_Strategis')->nullable();
            $table->unsignedTinyInteger('Keanekaragaman_Hayati')->nullable();
            $table->unsignedTinyInteger('Kualitas_Air')->nullable();
            $table->unsignedTinyInteger('Laut_Pesisir_dan_Pantai')->nullable();
            $table->unsignedTinyInteger('Kualitas_Udara')->nullable();
            $table->unsignedTinyInteger('Pengelolaan_Sampah_dan_Limbah')->nullable();
            $table->unsignedTinyInteger('Lahan_dan_Hutan')->nullable();
            $table->unsignedTinyInteger('Perubahan_Iklim')->nullable();
            $table->unsignedTinyInteger('Risiko_Bencana')->nullable();
            $table->unsignedTinyInteger('Penetapan_Isu_Prioritas')->nullable();
            $table->unsignedTinyInteger('Bab_3')->nullable();
            $table->unsignedTinyInteger('Bab_4')->nullable();
            $table->unsignedTinyInteger('Bab_5')->nullable();
            $table->float('Total_Skor')->nullable();
            $table->enum('status', ['parsed_ok', 'parsed_error', 'finalized'])->default('parsed_ok');
            $table->json('error_messages')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaian_slhd_parsed');
    }
};
