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

class TalentPassFailTest extends TestCase
{
    use RefreshDatabase;

    public function test_talent_page_renders_pass_fail_over_all_completed_attempts(): void
    {
        $grade = Grade::factory()->level(3)->create();
        $subject = Subject::factory()->availableIn($grade)->create();
        $chapter = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);

        $teacher = User::factory()->teacher()->create();
        $quiz = Quiz::factory()->for($chapter)->create(['teacher_id' => $teacher->id]);

        QuizAttempt::factory()->count(4)->for($quiz)->passed()->create();
        QuizAttempt::factory()->count(3)->for($quiz)->failed()->create();
        QuizAttempt::factory()->for($quiz)->incomplete()->create(); // not counted

        $response = $this->actingAs($teacher)->get(route('cikgu.bakat'));

        $response->assertOk();
        $passFail = $response->viewData('passFail');
        $this->assertSame(4, $passFail['passed']);
        $this->assertSame(3, $passFail['failed']);
        $this->assertSame(7, $passFail['total']);
    }
}
