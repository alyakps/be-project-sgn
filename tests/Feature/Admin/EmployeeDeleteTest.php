<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_karyawan_by_nik(): void
    {
        $admin = User::factory()->admin()->create();
        $karyawan = User::factory()->karyawan()->create([
            'nik' => '9999888877776666',
        ]);

        $res = $this->actingAs($admin)->deleteJson("/api/admin/karyawan/{$karyawan->nik}");

        $res->assertOk();

        $this->assertDatabaseMissing('users', [
            'nik' => '9999888877776666',
        ]);
    }
}
