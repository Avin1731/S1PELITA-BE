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
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('nama_region');
            $table->enum('type', ['provinsi','kabupaten/kota']);
            $table->foreignId('parent_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->enum('kategori',['kota_kecil','kota_sedang','kota_besar','kabupaten_kecil','kabupaten_sedang','kabupaten_besar'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
