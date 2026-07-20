<?php

namespace Tests\Feature\Admin;

use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_export_downloads_with_the_right_type_and_filename(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.laporan.pdf', ['period' => '30d']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('.pdf', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('30d', $response->headers->get('content-disposition'));
    }

    public function test_word_export_downloads_with_the_docx_mime_type(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.laporan.word', ['period' => '7d']));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $response->headers->get('content-type'),
        );
        $this->assertStringContainsString('.docx', $response->headers->get('content-disposition'));
    }

    public function test_an_invalid_period_falls_back_to_the_allow_listed_default(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.laporan.pdf', ['period' => 'hacked']))->assertOk();
    }

    public function test_exports_reject_non_admin_users(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student(3)->create();

        // The role middleware bounces a non-admin to their own home rather than serving the file.
        $this->actingAs($teacher)->get(route('admin.laporan.pdf'))
            ->assertRedirect($teacher->homeRoute());
        $this->actingAs($student)->get(route('admin.laporan.word'))
            ->assertRedirect($student->homeRoute());
    }

    public function test_generating_a_report_does_not_increment_analytics_counters(): void
    {
        $chapter = Chapter::factory()->create();
        $teacher = User::factory()->teacher()->create();
        $lesson = Lesson::factory()->for($chapter)->create(['teacher_id' => $teacher->id, 'views_count' => 7]);
        $material = Material::factory()->for($chapter)->create(['teacher_id' => $teacher->id, 'download_count' => 4]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.laporan.pdf'))->assertOk();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $this->assertSame(7, $lesson->fresh()->views_count);
        $this->assertSame(4, $material->fresh()->download_count);
    }
}
