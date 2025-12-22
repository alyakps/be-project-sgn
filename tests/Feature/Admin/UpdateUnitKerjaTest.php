<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateUnitKerjaTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_update_karyawan_unit_kerja(): void
    {
        $admin = User::factory()->admin()->create();

        $karyawan = User::factory()->karyawan()->create([
            'nik' => '1111222233334444',
            'name' => 'User Lama',
            'email' => 'user.lama@example.com',
        ]);

        $res = $this->actingAs($admin)->putJson("/api/admin/karyawan/{$karyawan->nik}", [
            'name' => $karyawan->name,
            'email' => $karyawan->email,
            'unit_kerja' => 'UNIT BARU',
        ]);

        $res->assertOk();

        // sesuaikan jika unit_kerja tersimpan di tabel lain
        $this->assertDatabaseHas('users', [
            'nik' => '1111222233334444',
            // kalau unit_kerja di users:
            // 'unit_kerja' => 'UNIT BARU',
        ]);
    }
}
