<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('nik', 32)->unique();
            $table->string('name');
            $table->string('unit_kerja')->nullable();
            $table->string('email')->unique();
            $table->string('password');

            // ✅ untuk nonaktifkan user (cancel import / disable account)
            $table->boolean('is_active')->default(true)->index();

            /**
             * ✅ Track import:
             * - import_log_id: tag "terakhir diimport/diperbarui" (opsional)
             * - created_import_log_id: tag "dibuat oleh import tertentu" (UNTUK CANCEL PRESISI)
             */
            $table->unsignedBigInteger('import_log_id')->nullable()->index();
            $table->unsignedBigInteger('created_import_log_id')->nullable()->index();

            $table->boolean('must_change_password')->default(false);

            $table->string('role')->default('karyawan'); // admin / karyawan
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
