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
        Schema::create('laporan_utama', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')
                ->constrained('submissions')
                ->onDelete('cascade');
            $table->string('path');
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
        Schema::dropIfExists('laporan_utama');
    }
};
