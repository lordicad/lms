<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The pass/fail breakdown used to live on the Talent (Bakat) page; it now sits on the teacher Home
 * dashboard, and the Bakat route redirects there. The counting rule is what matters here.
 */
class TalentPassFailTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_pass_fail_over_all_completed_attempts(): void
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

        $response->assertOk();
        $passFail = $response->viewData('passFail');
        $this->assertSame(4, $passFail['passed']);
        $this->assertSame(3, $passFail['failed']);
        $this->assertSame(7, $passFail['total']);
    }

    /**
     * The report scope and the student's celebration must agree on the 79.5% boundary: both round,
     * so a borderline attempt counts as a pass everywhere. A genuine sub-boundary attempt still fails
     * on both sides, so the rounding does not simply wave everything through.
     */
    public function test_scope_passed_agrees_with_the_student_on_the_rounding_boundary(): void
    {
        $quiz = Quiz::factory()->for(Chapter::factory()->create())->create();

        // 159/200 = 79.5% -> rounds to 80 -> the student is shown "lulus".
        $borderline = QuizAttempt::factory()->for($quiz)->create(['score' => 159, 'max_score' => 200, 'completed_at' => now()]);
        // 158/200 = 79.0% -> rounds to 79 -> a real fail on both sides (the control).
        $justUnder = QuizAttempt::factory()->for($quiz)->create(['score' => 158, 'max_score' => 200, 'completed_at' => now()]);

        $this->assertTrue($borderline->isPassed(), 'the student sees a pass at 79.5%');
        $this->assertTrue(
            QuizAttempt::whereKey($borderline->id)->passed()->exists(),
            'the report must count the same borderline attempt as a pass',
        );

        $this->assertFalse($justUnder->isPassed());
        $this->assertFalse(QuizAttempt::whereKey($justUnder->id)->passed()->exists());
    }
}
