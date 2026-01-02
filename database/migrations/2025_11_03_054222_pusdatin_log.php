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
        Schema::create('pusdatin_logs', function (Blueprint $table) {

            $table->id();
            $table->integer('year');
            $table->foreignId('submission_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('stage', ['review','penilaian_slhd','penilaian_penghargaan','validasi_1','validasi_2']);
            $table->enum('activity_type', ['approve','reject','upload','finalize','reopen']);
            $table->foreignId('actor_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');

            $table->enum('document_type', ['ringkasan_eksekutif', 'laporan_utama', 'tabel_utama', 'lampiran', 'iklh'])->nullable();

            $table->enum('status', ['approved', 'rejected','success','failed'])->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('actor_id');
            $table->index(['submission_id', 'document_type']);
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pusdatin_logs');
    }
};
