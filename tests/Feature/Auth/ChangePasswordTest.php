<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_password_with_correct_old_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPass123!'),
            'role' => 'karyawan',
        ]);

        $res = $this->actingAs($user)->postJson('/api/auth/change-password', [
            'current_password' => 'OldPass123!',
            'new_password' => 'NewPass123!',
            'new_password_confirmation' => 'NewPass123!',
        ]);

        $res->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass123!', $user->password));
    }

    public function test_change_password_fails_if_old_password_wrong(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPass123!'),
            'role' => 'karyawan',
        ]);

        $res = $this->actingAs($user)->postJson('/api/auth/change-password', [
            'current_password' => 'SALAH',
            'new_password' => 'NewPass123!',
            'new_password_confirmation' => 'NewPass123!',
        ]);

        // sesuai yang kamu sudah punya (backend balikin 422)
        $res->assertStatus(422);
    }
}
