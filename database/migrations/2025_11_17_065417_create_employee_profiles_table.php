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

            // 1. Nama Lengkap
            $table->string('nama_lengkap', 150)->nullable();

            // 2. Gelar Akademik
            $table->string('gelar_akademik', 100)->nullable();

            // 3. NIK (PN) → pakai nama 'nik' saja
            $table->string('nik', 50)->nullable();

            // 4. Pendidikan
            $table->string('pendidikan', 100)->nullable();

            // 5. Nomor KTP
            $table->string('no_ktp', 50)->nullable();

            // 6. Tempat Lahir
            $table->string('tempat_lahir', 100)->nullable();

            // 7. Tanggal Lahir
            $table->date('tanggal_lahir')->nullable();

            // 8. Jenis Kelamin
            $table->string('jenis_kelamin', 20)->nullable();
            // ex: "Laki-laki", "Perempuan"

            // 9. Agama
            $table->string('agama', 50)->nullable();

            // 10. Jabatan Terakhir
            $table->string('jabatan_terakhir', 150)->nullable();

            // 11. Alamat Rumah
            $table->text('alamat_rumah')->nullable();

            // 12. Handphone
            $table->string('handphone', 50)->nullable();

            // 13. E-mail (personal)
            $table->string('email_pribadi', 150)->nullable();

            // 14. NPWP
            $table->string('npwp', 50)->nullable();

            // 15. Suku
            $table->string('suku', 50)->nullable();

            // 16. Golongan Darah
            $table->string('golongan_darah', 5)->nullable();
            // ex: 'A', 'B', 'AB', 'O'

            // 17. Status Perkawinan
            $table->string('status_perkawinan', 50)->nullable();

            // 18. Penilaian Kerja
            $table->text('penilaian_kerja')->nullable();

            // 19. Achievement / Prestasi
            $table->text('pencapaian')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
