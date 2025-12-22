<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportCompetenciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_hard_competencies(): void
    {
        Excel::fake();

        $admin = User::factory()->admin()->create();

        $file = UploadedFile::fake()->create(
            'hard.xlsx',
            10,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $res = $this->actingAs($admin)->postJson('/api/admin/import-hard-competencies', [
            'file' => $file,
            'tahun' => 2025,
        ]);

        $res->assertOk();

        Excel::assertImported('hard.xlsx');
    }

    public function test_admin_can_import_soft_competencies(): void
    {
        Excel::fake();

        $admin = User::factory()->admin()->create();

        $file = UploadedFile::fake()->create(
            'soft.xlsx',
            10,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $res = $this->actingAs($admin)->postJson('/api/admin/import-soft-competencies', [
            'file' => $file,
            'tahun' => 2025,
        ]);

        $res->assertOk();

        Excel::assertImported('soft.xlsx');
    }
}
