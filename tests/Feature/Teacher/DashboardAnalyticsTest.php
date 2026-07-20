<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_bakat_url_redirects_to_dashboard(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get(route('cikgu.bakat'))
            ->assertRedirect(route('cikgu.dashboard'));
    }

    public function test_dashboard_renders_and_has_no_bakat_nav_link(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->get(route('cikgu.dashboard'));

        $response->assertOk();
        // The Bakat page still redirects, but the sidebar no longer links to it.
        $response->assertDontSee('href="'.route('cikgu.bakat').'"', false);
    }

    public function test_weekly_trend_has_seven_continuous_days_including_zero_days(): void
    {
        $teacher = User::factory()->teacher()->create();
        Lesson::factory()->create(['teacher_id' => $teacher->id]); // created today

        $response = $this->actingAs($teacher)->get(route('cikgu.dashboard'));

        $trend = $response->viewData('weeklyTrend');
        $this->assertCount(7, $trend['labels']);
        $this->assertCount(7, $trend['videos']);
        $this->assertSame(1, array_sum($trend['videos']));
    }

    public function test_pass_fail_uses_the_shared_pass_rule_over_all_completed_attempts(): void
    {
        $grade = Grade::factory()->level(3)->create();
        $subject = Subject::factory()->availableIn($grade)->create();
        $chapter = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);

        $teacher = User::factory()->teacher()->create();
        $quiz = Quiz::factory()->for($chapter)->create(['teacher_id' => $teacher->id]);

        QuizAttempt::factory()->count(4)->for($quiz)->passed()->create();
        QuizAttempt::factory()->count(3)->for($quiz)->failed()->create();
        QuizAttempt::factory()->for($quiz)->incomplete()->create(); // not counted

        $response = $this->actingAs($teacher)->get(route('cikgu.dashboard'));

        $passFail = $response->viewData('passFail');
        $this->assertSame(4, $passFail['passed']);
        $this->assertSame(3, $passFail['failed']);
        $this->assertSame(7, $passFail['total']);
    }

    public function test_content_metrics_expose_top_items_with_links(): void
    {
        $teacher = User::factory()->teacher()->create();
        Lesson::factory()->create(['teacher_id' => $teacher->id, 'views_count' => 50]);
        Lesson::factory()->create(['teacher_id' => $teacher->id, 'views_count' => 20]);

        $response = $this->actingAs($teacher)->get(route('cikgu.dashboard'));

        $metrics = $response->viewData('contentMetrics');
        $this->assertSame(70, $metrics['views']['total']);
        $this->assertSame(50, $metrics['views']['items'][0]['value']);
        $this->assertNotNull($metrics['views']['items'][0]['url']);
    }
}
