<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // untuk hash password

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'nik'      => '1000167', // ✅ tambahkan NIK
                'name'     => 'Admin',
                'password' => Hash::make('admin123'), // ✅ hash password
                'role'     => 'admin',
            ]
        );
    }
}
