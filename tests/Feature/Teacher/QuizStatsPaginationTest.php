<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizStatsPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function quizWithAttempts(User $teacher, int $completed, int $passed): Quiz
    {
        Grade::factory()->level(4)->create();
        $chapter = Chapter::factory()->create();
        $quiz = Quiz::factory()->for($chapter)->create([
            'teacher_id' => $teacher->id,
            'type' => Quiz::TYPE_INTERACTIVE,
        ]);

        QuizAttempt::factory()->count($passed)->for($quiz)->passed()->create();
        QuizAttempt::factory()->count($completed - $passed)->for($quiz)->failed()->create();

        return $quiz;
    }

    public function test_attempts_paginate_at_ten_and_page_two_starts_at_eleven(): void
    {
        $teacher = User::factory()->teacher()->create();
        $quiz = $this->quizWithAttempts($teacher, completed: 25, passed: 10);

        $page1 = $this->actingAs($teacher)->get(route('cikgu.kuiz.statistik', $quiz));
        $page1->assertOk();
        $attempts = $page1->viewData('attempts');
        $this->assertSame(10, $attempts->perPage());
        $this->assertSame(25, $attempts->total());
        $this->assertSame(1, $attempts->firstItem());

        $page2 = $this->actingAs($teacher)->get(route('cikgu.kuiz.statistik', [$quiz, 'page' => 2]));
        $page2->assertOk();
        $this->assertSame(11, $page2->viewData('attempts')->firstItem());
    }

    public function test_summaries_use_all_completed_attempts_not_only_the_page(): void
    {
        $teacher = User::factory()->teacher()->create();
        $quiz = $this->quizWithAttempts($teacher, completed: 25, passed: 18);

        $response = $this->actingAs($teacher)->get(route('cikgu.kuiz.statistik', $quiz));

        $response->assertOk();
        $this->assertSame(25, $response->viewData('completedCount'));
        $this->assertSame(18, $response->viewData('passedCount'));
        $this->assertSame(7, $response->viewData('failedCount'));
    }

    public function test_another_teacher_cannot_view_the_stats(): void
    {
        $owner = User::factory()->teacher()->create();
        $other = User::factory()->teacher()->create();
        $quiz = $this->quizWithAttempts($owner, completed: 3, passed: 1);

        $this->actingAs($other)->get(route('cikgu.kuiz.statistik', $quiz))->assertForbidden();
    }
}
