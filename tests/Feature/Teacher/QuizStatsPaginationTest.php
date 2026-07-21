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

    private function quizWithAttempts(User $teacher, int $completed): Quiz
    {
        Grade::factory()->level(4)->create();
        $chapter = Chapter::factory()->create();
        $quiz = Quiz::factory()->for($chapter)->create([
            'teacher_id' => $teacher->id,
            'type' => Quiz::TYPE_INTERACTIVE,
        ]);

        QuizAttempt::factory()->count($completed)->for($quiz)->passed()->create();

        return $quiz;
    }

    public function test_attempts_paginate_at_ten_and_page_two_starts_at_eleven(): void
    {
        $teacher = User::factory()->teacher()->create();
        $quiz = $this->quizWithAttempts($teacher, 25);

        $page1 = $this->actingAs($teacher)->get(route('cikgu.kuiz.statistik', $quiz));
        $page1->assertOk();
        $this->assertSame(10, $page1->viewData('attempts')->perPage());
        $this->assertSame(25, $page1->viewData('attempts')->total());
        $this->assertSame(25, $page1->viewData('completedCount'));

        $page2 = $this->actingAs($teacher)->get(route('cikgu.kuiz.statistik', [$quiz, 'page' => 2]));
        $this->assertSame(11, $page2->viewData('attempts')->firstItem());
    }
}
