<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soft_competencies', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 32)->index();
            $table->unsignedSmallInteger('tahun')->index();

            $table->string('id_kompetensi', 32);
            $table->string('kode', 64)->index();
            $table->string('nama_kompetensi', 255);
            $table->enum('status', ['tercapai', 'tidak tercapai']);
            $table->unsignedTinyInteger('nilai'); // 0–100
            $table->text('deskripsi')->nullable();

            $table->timestamps();

            // 1 NIK + 1 kompetensi per tahun → unik
            $table->unique(['nik', 'id_kompetensi', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soft_competencies');
    }
};
