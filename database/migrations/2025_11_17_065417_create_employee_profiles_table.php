<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel users (wajib 1:1)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->unique();

            // ✅ tag asal import (tanpa FK, aman untuk migrate order)
            $table->unsignedBigInteger('import_log_id')->nullable()->index();

            $table->string('photo_path', 255)->nullable();

            $table->string('nama_lengkap', 150)->nullable();
            $table->string('gelar_akademik', 100)->nullable();
            $table->string('nik', 50)->nullable();
            $table->string('pendidikan', 100)->nullable();
            $table->string('no_ktp', 16)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin', 20)->nullable();
            $table->string('agama', 50)->nullable();
            $table->string('jabatan_terakhir', 150)->nullable();
            $table->string('unit_kerja', 100)->nullable();
            $table->text('alamat_rumah')->nullable();
            $table->string('handphone', 50)->nullable();
            $table->string('email_pribadi', 150)->nullable();
            $table->string('npwp', 50)->nullable();
            $table->string('suku', 50)->nullable();
            $table->string('golongan_darah', 5)->nullable();
            $table->string('status_perkawinan', 50)->nullable();
            $table->text('penilaian_kerja')->nullable();
            $table->text('pencapaian')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
