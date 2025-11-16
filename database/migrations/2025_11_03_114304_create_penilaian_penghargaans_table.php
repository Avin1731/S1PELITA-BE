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
        Schema::create('penilaian_penghargaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penilaian_slhd_ref_id')->constrained('penilaian_slhd')->onDelete('cascade');
            $table->year('year');
            $table->enum('status', ['uploaded', 'parsing', 'parsed_ok','parsed_failed', 'finalized'])->default('uploaded');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->string('file_path'); // lokasi file excel yang diupload
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->boolean('is_finalized')->default(false);
            $table->text('catatan')->nullable(); // catatan umum penilaian
            $table->text('error_messages')->nullable(); // catatan error jika parsing gagal
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaian_penghargaan');
    }
};
