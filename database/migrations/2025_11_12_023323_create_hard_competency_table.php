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
        Schema::create('hard_competencies', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 32)->index();                     // ID unik user
            $table->unsignedSmallInteger('tahun')->index();         // ✅ tahun penilaian, misal 2025
            $table->string('id_kompetensi', 32);                    // Contoh: 4821
            $table->string('kode', 64)->index();                    // Contoh: HAK.MAK.008
            $table->string('nama_kompetensi', 255);
            $table->string('job_family_kompetensi', 128);
            $table->string('sub_job_family_kompetensi', 128)->nullable();
            $table->enum('status', ['tercapai', 'tidak tercapai']);
            $table->unsignedTinyInteger('nilai');                   // 0–100
            $table->text('deskripsi')->nullable();
            $table->timestamps();

            // ✅ 1 user boleh punya kode yang sama di tahun berbeda
            $table->unique(['nik', 'kode', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hard_competencies');
    }
};
