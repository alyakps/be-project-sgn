<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();

            $table->string('filename');

            // ✅ simpan path file upload supaya bisa dihapus saat dibatalkan
            $table->string('stored_path')->nullable();

            $table->string('type', 50)->index();   // 'hard_competency' | 'soft_competency' | 'karyawan'
            $table->integer('tahun')->nullable()->index();

            $table->integer('sukses')->default(0);
            $table->integer('gagal')->default(0);

            // ✅ status untuk UI: done / canceled
            $table->string('status', 20)->default('done')->index(); // done | canceled
            $table->timestamp('canceled_at')->nullable();

            // uploader (FK aman, karena users sudah ada)
            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // canceled_by (FK aman, karena users sudah ada)
            $table->foreignId('canceled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
