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
        Schema::create('validasi_2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('validasi_1_id')->constrained('validasi_1')->onDelete('cascade');
            $table->year('year');
            $table->enum('status', ['parsed_ok', 'finalized'])->default('parsed_ok');
            $table->boolean('is_finalized')->default(false);
            $table->timestamp('finalized_at')->nullable();
            $table->text('catatan')->nullable(); 
            $table->text('error_messages')->nullable(); 
            $table->foreignId('finalized_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['validasi_1_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validasi_2');
    }
};
