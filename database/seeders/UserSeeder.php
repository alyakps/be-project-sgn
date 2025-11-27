<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // === ADMIN === //
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'nik'      => '1000167',
                'name'     => 'Admin',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
            ]
        );
    }
}
