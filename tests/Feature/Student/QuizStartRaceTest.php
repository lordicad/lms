<?php

namespace Tests\Feature\Student;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use App\Services\QuizGrader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Starting a quiz must never produce two ranked attempts for the same student and quiz.
 *
 * The leaderboard sums every ranked attempt, so a duplicate (double-tap, or web + mobile at once)
 * counted that quiz's points twice. Starting now goes through QuizGrader::startOrResume, which
 * reuses the open attempt under a lock.
 */
class QuizStartRaceTest extends TestCase
{
    use RefreshDatabase;

    private function quiz(): Quiz
    {
        $grade = Grade::factory()->level(3)->create();
        $subject = Subject::factory()->availableIn($grade)->create();
        $chapter = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);

        return Quiz::factory()->for($chapter)->create();
    }

    public function test_starting_twice_reuses_the_open_attempt(): void
    {
        $quiz = $this->quiz();
        $student = User::factory()->student(3)->create();
        $grader = app(QuizGrader::class);

        $first = $grader->startOrResume($quiz, $student);
        $second = $grader->startOrResume($quiz, $student);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, QuizAttempt::where('student_id', $student->id)->where('counts_for_ranking', true)->count());
    }

    public function test_a_retry_after_finishing_is_practice_not_ranked(): void
    {
        $quiz = $this->quiz();
        $student = User::factory()->student(3)->create();
        $grader = app(QuizGrader::class);

        $first = $grader->startOrResume($quiz, $student);
        $first->update(['completed_at' => now()]);

        $retry = $grader->startOrResume($quiz, $student);

        $this->assertNotSame($first->id, $retry->id);
        $this->assertTrue($first->fresh()->counts_for_ranking);
        $this->assertFalse($retry->counts_for_ranking);
        $this->assertSame(1, QuizAttempt::where('student_id', $student->id)->where('counts_for_ranking', true)->count());
    }
}
