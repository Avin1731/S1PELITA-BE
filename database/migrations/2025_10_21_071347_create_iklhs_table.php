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
        Schema::create('iklh', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')
                ->constrained('submissions')
                ->onDelete('cascade');

            $table->decimal('indeks_kualitas_air', 8, 2)->nullable();
            $table->decimal('indeks_kualitas_udara', 8, 2)->nullable();
            $table->decimal('indeks_kualitas_lahan', 8, 2)->nullable();
            $table->decimal('indeks_kualitas_kehati', 8, 2)->nullable();
            $table->decimal('indeks_kualitas_pesisir_laut', 8, 2)->nullable();

            $table->enum('status', ['draft', 'finalized', 'rejected', 'approved'])->default('draft');
            $table->text('catatan_admin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iklh');
    }
};
