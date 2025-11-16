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
        Schema::create('penilaian_penghargaan_parsed', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penilaian_penghargaan_id')->constrained('penilaian_penghargaan')->onDelete('cascade');
            $table->foreignId('id_dinas')->constrained('dinas')->onDelete('cascade')->nullable();
            $table->string('nama_dinas')->default('tidak diketahui')->nullable();
                // Adipura
            $table->integer('Adipura_Jumlah_Wilayah')->nullable()->default(0);
            $table->integer('Adipura_Skor_Max')->nullable()->default(0);
            $table->integer('Adipura_Skor')->nullable()->default(0);
            
            // Adiwiyata
            $table->integer('Adiwiyata_Jumlah_Sekolah')->nullable()->default(0);
            $table->integer('Adiwiyata_Skor_Max')->nullable()->default(0);
            $table->integer('Adiwiyata_Skor')->nullable()->default(0);
            
            // Proklim
            $table->integer('Proklim_Jumlah_Desa')->nullable()->default(0);
            $table->integer('Proklim_Skor_Max')->nullable()->default(0);
            $table->integer('Proklim_Skor')->nullable()->default(0);
            
            // Proper
            $table->integer('Proper_Jumlah_Perusahaan')->nullable()->default(0);
            $table->integer('Proper_Skor_Max')->nullable()->default(0);
            $table->integer('Proper_Skor')->nullable()->default(0);
                   
            $table->integer('Kalpataru_Jumlah_Penerima')->nullable()->default(0);
            $table->integer('Kalpataru_Skor_Max')->nullable()->default(0);
            $table->integer('Kalpataru_Skor')->nullable()->default(0);
            
            $table->float('Adipura_Persentase')->nullable();
            $table->float('Adiwiyata_Persentase')->nullable();
            $table->float('Proklim_Persentase')->nullable();
            $table->float('Proper_Persentase')->nullable();
            $table->float('Kalpataru_Persentase')->nullable();  
            $table->float('Total_Skor')->nullable();
            $table->json('error_messages')->nullable();
            $table->enum('status', ['parsed_ok', 'parsed_error','finalized'])->default('parsed_error');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaian_penghargaan_parsed');
    }
};
