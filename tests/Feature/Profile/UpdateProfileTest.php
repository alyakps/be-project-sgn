<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_update_profile_with_valid_mandatory_fields(): void
    {
        $user = User::factory()->create([
            'role' => 'karyawan',
        ]);

        $payload = [
            'handphone'         => '081234567890',
            'no_ktp'            => '3579010101010001',
            'tanggal_lahir'     => '1998-01-01',
            'alamat_rumah'      => 'Jl. Mawar No. 1',
            'status_perkawinan' => 'Belum Kawin',
        ];

        $res = $this
            ->actingAs($user)
            ->postJson('/api/karyawan/profile', $payload);

        $res->assertOk();

        $this->assertDatabaseHas('employee_profiles', [
            'user_id'      => $user->id,
            'handphone'    => '081234567890',
            'no_ktp'       => '3579010101010001',
        ]);
    }

    /** @test */
    public function update_profile_requires_mandatory_fields(): void
    {
        $user = User::factory()->create([
            'role' => 'karyawan',
        ]);

        // ❌ sengaja KOSONG
        $payload = [
            'handphone'         => '',
            'no_ktp'            => '',
            'tanggal_lahir'     => '',
            'alamat_rumah'      => '',
            'status_perkawinan' => '',
        ];

        $res = $this
            ->actingAs($user)
            ->postJson('/api/karyawan/profile', $payload);

        $res->assertStatus(422)
            ->assertJsonValidationErrors([
                'handphone',
                'no_ktp',
                'tanggal_lahir',
                'alamat_rumah',
                'status_perkawinan',
            ]);
    }
}
