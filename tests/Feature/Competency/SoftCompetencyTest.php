<?php

namespace Tests\Feature\Competency;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftCompetencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_soft_competency_by_year(): void
    {
        $user = User::factory()->create();

        // TODO: seed/factory soft competency

        $res = $this->actingAs($user)->getJson('/api/karyawan/soft-competencies?year=2025');
        $res->assertOk();

            }
}
