<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_dashboard_summary_all_year(): void
    {
        $user = User::factory()->create();

        // TODO: seed nilai hard/soft lintas tahun untuk uji average

        $res = $this->actingAs($user)->getJson('/api/dashboard/karyawan/summary?year=all');
        $res->assertOk();

            }
}
