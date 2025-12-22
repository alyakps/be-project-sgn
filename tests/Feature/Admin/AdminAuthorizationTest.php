<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_karyawan_cannot_access_admin_routes(): void
    {
        $karyawan = User::factory()->karyawan()->create();

        $this->actingAs($karyawan)
            ->getJson('/api/admin/import-logs')
            ->assertStatus(403);
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/api/admin/import-logs')
            ->assertStatus(200);
    }
}
