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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // AKTOR: Siapa yang melakukan aksi?
            // Menggunakan onDelete('set null') agar jika user dihapus, log tetap ada (history aman)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // ACTION: Jenis aksi (misal: 'login', 'create_user', 'upload_dokumen', 'delete_account')
            $table->string('action');

            // DESCRIPTION: Keterangan detail human-readable (misal: "Menyetujui akun DLH Jawa Barat")
            // Ini yang akan tampil di kolom "AKSI" pada UI
            $table->text('description');

            // SUBJECT (Polymorphic): Objek apa yang kena dampak?
            // Ini membuat kolom 'subject_type' dan 'subject_id' otomatis.
            // Berguna jika ingin link ke data asli (misal: link ke User yg diedit, atau Dokumen yg diupload)
            $table->nullableMorphs('subject');

            // PROPERTIES: Data tambahan dalam format JSON
            // Berguna untuk menyimpan data backup (misal: data user sebelum dihapus/diedit)
            $table->json('properties')->nullable();

            // DATA TEKNIS: Untuk audit keamanan
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // WAKTU: Kolom 'created_at' akan dipakai untuk kolom "WAKTU" di UI
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};