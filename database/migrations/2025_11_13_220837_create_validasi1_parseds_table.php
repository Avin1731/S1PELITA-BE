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
        Schema::create('validasi_1_parseds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('validasi_1_id')->constrained('validasi_1')->onDelete('cascade');
            $table->foreignId('id_dinas')->constrained('dinas')->onDelete('cascade')->nullable();
            $table->string('nama_dinas')->default('tidak diketahui')->nullable();
            $table->float('Total_Skor')->nullable();
            $table->float('Nilai_IKLH')->nullable();
            $table->float('Nilai_Penghargaan')->nullable();
            $table->enum('status', ['parsed_ok', 'parsed_error', 'finalized'])->default('parsed_ok');
            $table->enum('status_result',['lulus','tidak_lulus'])->nullable();

            $table->json('error_messages')->nullable();
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validasi_1_parseds');
    }
};
