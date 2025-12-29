<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hard_competencies', function (Blueprint $table) {
            $table->id();

            $table->string('nik', 32)->index();
            $table->unsignedSmallInteger('tahun')->index();
            $table->string('id_kompetensi', 32);

            $table->string('kode', 64)->index();
            $table->string('nama_kompetensi', 255);
            $table->string('job_family_kompetensi', 128);
            $table->string('sub_job_family_kompetensi', 128)->nullable();
            $table->enum('status', ['tercapai', 'tidak tercapai']);
            $table->unsignedTinyInteger('nilai'); // 0–100
            $table->text('deskripsi')->nullable();

            // ✅ untuk "Batalkan": kompetensi hasil import dinonaktifkan
            $table->boolean('is_active')->default(true)->index();

            // ✅ tag asal import (tanpa FK)
            $table->unsignedBigInteger('import_log_id')->nullable()->index();

            $table->timestamps();

            $table->unique(['nik', 'id_kompetensi', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hard_competencies');
    }
};
