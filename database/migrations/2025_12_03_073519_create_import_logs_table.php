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
            $table->string('type', 50)->index();   // 'hard' atau 'soft'
            $table->integer('tahun')->nullable()->index();
            $table->integer('sukses')->default(0);
            $table->integer('gagal')->default(0);
            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
