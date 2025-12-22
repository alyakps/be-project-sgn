<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportKaryawanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_karyawan_file(): void
    {
        Excel::fake(); // ⬅️ INI KUNCINYA

        $admin = User::factory()->admin()->create();

        $file = UploadedFile::fake()->create(
            'karyawan.xlsx',
            10,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $res = $this->actingAs($admin)->postJson('/api/admin/import-karyawan', [
            'file' => $file,
        ]);

        $res->assertOk();

        // memastikan import dipanggil
        Excel::assertImported('karyawan.xlsx');
    }

    public function test_import_karyawan_rejects_invalid_file_type(): void
    {
        $admin = User::factory()->admin()->create();

        $file = UploadedFile::fake()->create('karyawan.pdf', 10, 'application/pdf');

        $this->actingAs($admin)->postJson('/api/admin/import-karyawan', [
            'file' => $file,
        ])->assertStatus(422);
    }
}
