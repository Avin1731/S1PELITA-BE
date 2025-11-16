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
        Schema::create('validasi_1', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penilaian_penghargaan_ref_id')->constrained('penilaian_penghargaan')->onDelete('cascade');
            $table->year('year');
            $table->enum('status', ['parsed_ok', 'finalized'])->default('parsed_ok');
            $table->boolean('is_finalized')->default(false);
            $table->timestamp('finalized_at')->nullable();
            $table->text('catatan')->nullable(); // catatan umum validasi 1
            $table->text('error_messages')->nullable(); // catatan error jika ada
            $table->foreignId('finalized_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validasi_1');
    }
};
