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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_dinas')
                  ->constrained('dinas')
                  ->onDelete('cascade');

            $table->integer('tahun');
            $table->enum('status', ['draft','finalized','approved'])
                  ->default('draft');
            $table->enum('finalized_by',['user','deadline'])->nullable();
            $table->text('catatan_admin')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->unique(['id_dinas', 'tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
