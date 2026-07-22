<?php

namespace Tests\Feature\Admin;

use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\AdminReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The report service reads its figures for the signed-in admin's school, so these need an admin
     * present even when calling it directly — otherwise the scope has no school and returns nothing.
     * The factory puts everyone in the same school, so the teachers below are this admin's own.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_contributor_ranking_uses_the_formula_and_deterministic_tie_breaks(): void
    {
        $chapter = Chapter::factory()->create();

        $a = User::factory()->teacher()->create(['name' => 'Aaa']);
        $b = User::factory()->teacher()->create(['name' => 'Bbb']);

        // Both have a total of 3, but A has more videos, so A ranks first on the tie-break.
        Lesson::factory()->count(2)->for($chapter)->create(['teacher_id' => $a->id]);
        Quiz::factory()->for($chapter)->create(['teacher_id' => $a->id]);

        Lesson::factory()->for($chapter)->create(['teacher_id' => $b->id]);
        Material::factory()->count(2)->for($chapter)->create(['teacher_id' => $b->id]);

        $ranked = app(AdminReportService::class)->contributors();

        $this->assertSame($a->id, $ranked[0]['id']);
        $this->assertSame(3, $ranked[0]['total']);
        $this->assertSame($b->id, $ranked[1]['id']);
    }

    public function test_top_content_uses_the_right_metrics_with_teacher_attribution(): void
    {
        $chapter = Chapter::factory()->create();
        $teacher = User::factory()->teacher()->create(['name' => 'Cikgu Top']);

        Lesson::factory()->for($chapter)->create(['teacher_id' => $teacher->id, 'title' => 'Popular Video', 'views_count' => 99]);
        Lesson::factory()->for($chapter)->create(['teacher_id' => $teacher->id, 'views_count' => 3]);
        Material::factory()->for($chapter)->create(['teacher_id' => $teacher->id, 'title' => 'Popular PDF', 'download_count' => 42]);
        $quiz = Quiz::factory()->for($chapter)->create(['teacher_id' => $teacher->id, 'title' => 'Popular Quiz']);
        QuizAttempt::factory()->count(5)->for($quiz)->create();

        $top = app(AdminReportService::class)->topContent();

        $this->assertSame('Popular Video', $top['video']['title']);
        $this->assertSame(99, $top['video']['count']);
        $this->assertSame('Cikgu Top', $top['video']['teacher']);
        $this->assertSame(42, $top['material']['count']);
        $this->assertSame(5, $top['quiz']['count']);
    }

    public function test_platform_activity_returns_correct_bucket_counts_for_each_period(): void
    {
        $report = app(AdminReportService::class);

        $this->assertCount(7, $report->platformActivity('7d')['labels']);
        $this->assertCount(30, $report->platformActivity('30d')['labels']);
        $this->assertCount(12, $report->platformActivity('12m')['labels']);

        $chapter = Chapter::factory()->create();
        $quiz = Quiz::factory()->for($chapter)->create();
        QuizAttempt::factory()->for($quiz)->passed()->create(['completed_at' => now()]);
        Lesson::factory()->for($chapter)->create(['created_at' => now()]);

        $activity = $report->platformActivity('7d');
        // Today is the last bucket; zero days are included before it.
        $this->assertSame(1, end($activity['series']['completed']));
        $this->assertSame(1, end($activity['series']['passed']));
        $this->assertGreaterThanOrEqual(1, end($activity['series']['uploads']));
        $this->assertSame(0, $activity['series']['completed'][0]);
    }

    public function test_registrations_include_only_accounts_from_the_last_seven_days(): void
    {
        $recent = User::factory()->student(3)->create(['created_at' => now()->subDays(2)]);
        $old = User::factory()->student(3)->create(['created_at' => now()->subDays(10)]);

        $ids = app(AdminReportService::class)->recentRegistrationsQuery()->pluck('id');

        $this->assertContains($recent->id, $ids);
        $this->assertNotContains($old->id, $ids);
    }

    public function test_dashboard_renders_with_summary_totals(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertIsArray($response->viewData('totals'));
    }

    public function test_pending_oversight_surfaces_real_signals_for_the_report(): void
    {
        $chapter = Chapter::factory()->create();
        Lesson::factory()->for($chapter)->draft()->create();

        $pending = app(AdminReportService::class)->pending();

        $titles = collect($pending)->pluck('title')->implode(' ');
        $this->assertStringContainsString('video belum diterbitkan', $titles);
    }

    public function test_admin_bakat_page_renders(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.bakat'))->assertOk();
    }

    public function test_full_contributor_ranking_page_paginates(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(30)->teacher()->create();

        $response = $this->actingAs($admin)->get(route('admin.penyumbang'));

        $response->assertOk();
        $this->assertSame(25, $response->viewData('contributors')->perPage());
    }
}
