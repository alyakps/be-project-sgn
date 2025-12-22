<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_logout(): void
    {
        $admin = User::factory()->admin()->create();

        // penting: ini bikin PersonalAccessToken sehingga logout bisa delete token
        Sanctum::actingAs($admin);

        $res = $this->postJson('/api/auth/logout');

        $res->assertOk();
    }
}
