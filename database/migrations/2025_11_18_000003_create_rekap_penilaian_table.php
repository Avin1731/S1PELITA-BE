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
        Schema::create('rekap_penilaian', function (Blueprint $table) {
            $table->id();
            $table->year('year');
            $table->integer('id_dinas');
            $table->string('nama_dinas');
            
            // Nilai dari setiap tahap
            $table->decimal('nilai_slhd', 10, 3)->nullable();
            $table->boolean('lolos_slhd')->default(false);
            
            $table->decimal('nilai_penghargaan', 10, 3)->nullable();
            $table->boolean('masuk_penghargaan')->default(false);
            
            $table->decimal('nilai_iklh', 10, 3)->nullable();
            $table->decimal('total_skor_validasi1', 10, 3)->nullable();
            $table->boolean('lolos_validasi1')->default(false);
            
            $table->boolean('kriteria_wtp')->nullable();
            $table->boolean('kriteria_kasus_hukum')->nullable();
            $table->boolean('lolos_validasi2')->default(false);
            
            // Wawancara (tahap terakhir)
            $table->decimal('nilai_wawancara', 5, 2)->nullable();
            $table->boolean('lolos_wawancara')->default(false);
            
            // Total skor final: 90% SLHD + 10% Wawancara
            $table->decimal('total_skor_final', 10, 3)->nullable();
            $table->integer('peringkat_final')->nullable();
            
            // Peringkat sementara (dari validasi 2)
            $table->integer('peringkat')->nullable();
            $table->enum('status_akhir', [
                'menunggu_penilaian_slhd',
                'lolos_slhd',
                'tidak_lolos_slhd', 
                'menunggu_penilaian_penghargaan',
                'lolos_penghargaan',
                'tidak_lolos_penghargaan', 
                'menunggu_validasi1',
                'lolos_validasi1',
                'tidak_lolos_validasi1', 
                'menunggu_validasi2',
                'lolos_validasi2',
                'tidak_lolos_validasi2',
                'menunggu_wawancara',
                // 'lolos_wawancara', 
                // 'tidak_lolos_wawancara',
                'selesai'
                // 'lolos_final'
            ])->default('tidak_lolos_slhd');
            
            $table->timestamps();
            
            $table->unique(['year', 'id_dinas']);
            $table->index(['year', 'status_akhir']);
            $table->index(['year', 'peringkat']);
            $table->index(['year', 'peringkat_final']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_penilaian');
    }
};
