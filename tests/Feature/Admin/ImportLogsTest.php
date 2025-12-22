<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_import_logs(): void
    {
        $admin = User::factory()->admin()->create();

        $res = $this->actingAs($admin)->getJson('/api/admin/import-logs');

        $res->assertOk();
    }
}
