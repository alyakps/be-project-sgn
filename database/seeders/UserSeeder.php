<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'alyakps@gmail.com'],
            ['password' => 'password123'] // otomatis ter-hash
        );        
        User::updateOrCreate(
            ['email' => 'aan@gmail.com'],
            ['password' => 'password123'] // otomatis ter-hash
        );
    }
}
