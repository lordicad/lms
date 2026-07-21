<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_engagement_summary_stats(): void
    {
        $chapter = Chapter::factory()->create();
        $teacher = User::factory()->teacher()->create();

        $lesson = Lesson::factory()->for($chapter)->create(['teacher_id' => $teacher->id]);
        Lesson::where('id', $lesson->id)->update(['views_count' => 4]);
        $material = Material::factory()->for($chapter)->create(['teacher_id' => $teacher->id]);
        Material::where('id', $material->id)->update(['download_count' => 9]);
        $quiz = Quiz::factory()->for($chapter)->create(['teacher_id' => $teacher->id]);
        QuizAttempt::factory()->count(3)->for($quiz)->create();

        $response = $this->actingAs($teacher)->get(route('cikgu.dashboard'));

        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertSame(4, $stats['views']);
        $this->assertSame(0, $stats['favourites']);
        $this->assertSame(9, $stats['downloads']);
        $this->assertSame(3, $stats['attempts']);

        // Talent signals on Home: pass/fail totals + the four leaderboards.
        $this->assertSame(3, $response->viewData('passFail')['total']);
        $this->assertCount(4, $response->viewData('lists'));
    }

    public function test_pass_fail_uses_the_shared_pass_rule(): void
    {
        $chapter = Chapter::factory()->create();
        $teacher = User::factory()->teacher()->create();
        $quiz = Quiz::factory()->for($chapter)->create(['teacher_id' => $teacher->id]);

        QuizAttempt::factory()->count(2)->for($quiz)->passed()->create();
        QuizAttempt::factory()->count(5)->for($quiz)->failed()->create();

        $passFail = $this->actingAs($teacher)->get(route('cikgu.dashboard'))->viewData('passFail');

        $this->assertSame(2, $passFail['passed']);
        $this->assertSame(5, $passFail['failed']);
        $this->assertSame(7, $passFail['total']);
    }

    public function test_old_talent_page_redirects_to_home(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('cikgu.bakat'))
            ->assertRedirect(route('cikgu.dashboard'));
    }
}
