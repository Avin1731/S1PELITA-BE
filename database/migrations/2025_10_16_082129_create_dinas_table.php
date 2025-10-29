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
        Schema::create('dinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('regions')->onDelete('cascade');
            $table->string('nama_dinas');
            $table->string('kode_dinas')->unique();
            $table->enum('status', ['belum_terdaftar', 'terdaftar','nonaktif'])->default('belum_terdaftar');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dinas');
    }
};
