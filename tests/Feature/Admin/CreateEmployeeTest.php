<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateEmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_karyawan(): void
    {
        $admin = User::factory()->admin()->create();

        $payload = [
            'nik' => '1234567890123456',
            'name' => 'Karyawan Baru',

            // aman untuk rule email:rfc,dns / rule ketat lain
            'email' => 'karyawan.baru@gmail.com',

            'role' => 'karyawan',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'unit_kerja' => 'UNIT A',
        ];

        $res = $this->actingAs($admin)->postJson('/api/admin/karyawan', $payload);

        // kalau controller kamu return 201, ini akan pass
        // kalau ternyata return 200, ganti assertStatus(201) -> assertStatus(200)
        $res->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'nik' => '1234567890123456',
            'email' => 'karyawan.baru@gmail.com',
            'role' => 'karyawan',
        ]);
    }
}
