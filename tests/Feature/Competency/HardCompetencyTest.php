<?php

namespace Tests\Feature\Competency;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HardCompetencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_hard_competency_by_year(): void
    {
        $user = User::factory()->create();

        // TODO: Seed / factory data hard competency untuk user dan tahun tertentu
        // Contoh pseudo:
        // HardScore::factory()->create(['user_id'=>$user->id,'year'=>2025,'score'=>92]);

        $res = $this->actingAs($user)->getJson('/api/karyawan/hard-competencies?year=2025');
        $res->assertOk();
    }

    public function test_user_cannot_view_other_user_hard_competency(): void
    {
        $userA = User::factory()->create(['role' => 'karyawan']);
        $userB = User::factory()->create(['role' => 'karyawan']);

        // Karyawan mencoba akses endpoint admin by NIK → harus ditolak (403)
        $this->actingAs($userA)
            ->getJson("/api/admin/karyawan/{$userB->nik}/hard-competencies?year=2025")
            ->assertStatus(403);
    }
}
