<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 32)->unique(); // 🔹 NIK unik untuk tiap user
            $table->string('name');                       // dipakai import Excel
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('karyawan');  // admin / karyawan
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
