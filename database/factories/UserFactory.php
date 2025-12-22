<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nik' => $this->faker->unique()->numerify('################'), // 16 digit
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('Password123!'),
            'remember_token' => Str::random(10),

            // default role → karyawan (AMAN untuk kebanyakan test)
            'role' => 'karyawan',
        ];
    }

    /**
     * State: admin
     * Dipakai untuk test endpoint /api/admin/*
     */
    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => 'admin',
        ]);
    }

    /**
     * State: karyawan
     * Biar eksplisit & konsisten di test
     */
    public function karyawan(): static
    {
        return $this->state(fn () => [
            'role' => 'karyawan',
        ]);
    }
}
