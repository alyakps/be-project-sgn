<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewEmployeeCompetenciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_karyawan_hard_competencies(): void
    {
        $admin = User::factory()->admin()->create();
        $karyawan = User::factory()->karyawan()->create(['nik' => '1234500000000000']);

        $this->actingAs($admin)
            ->getJson("/api/admin/karyawan/{$karyawan->nik}/hard-competencies?year=2025")
            ->assertOk();
    }

    public function test_admin_can_view_karyawan_soft_competencies(): void
    {
        $admin = User::factory()->admin()->create();
        $karyawan = User::factory()->karyawan()->create(['nik' => '1234500000000001']);

        $this->actingAs($admin)
            ->getJson("/api/admin/karyawan/{$karyawan->nik}/soft-competencies?year=2025")
            ->assertOk();
    }
}
