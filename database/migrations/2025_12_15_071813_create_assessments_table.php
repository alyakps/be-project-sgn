<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();

            // relasi ke user
            $table->unsignedBigInteger('user_id');

            // snapshot unit kerja (karena master unit kerja ada di CSV)
            $table->string('unit_kerja')->index();

            // tahun penilaian (annual)
            $table->integer('tahun_penilaian')->index();

            // nilai
            $table->decimal('hard_score', 5, 2);
            $table->decimal('soft_score', 5, 2);

            $table->timestamps();

            // index untuk performa dashboard
            $table->index(['unit_kerja', 'tahun_penilaian']);
            $table->index(['user_id', 'tahun_penilaian']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
