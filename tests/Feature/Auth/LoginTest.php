<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_successfully(): void
    {
        User::factory()->create([
            'email' => 'budi@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'karyawan',
        ]);

        $res = $this->postJson('/api/auth/login', [
            'email' => 'budi@example.com',
            'password' => 'Password123!',
        ]);

        $res->assertOk()
            ->assertJsonStructure([
                // AuthController kamu kemungkinan return token + user
                // Kalau nama key beda, nanti kita sesuaikan lagi
                'token',
                'user',
            ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'budi@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'karyawan',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'budi@example.com',
            'password' => 'SALAH',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);

    }
}
