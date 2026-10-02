<?php

namespace Tests\Feature\Student;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The repair for points doubled by the old double-start race: one ranked attempt per student and
 * quiz (the earliest completed), the rest kept as practice, nothing deleted.
 */
class DedupeRankedAttemptsTest extends TestCase
{
    use RefreshDatabase;

    private function runRepair(): void
    {
        (require database_path('migrations/2026_10_02_000001_dedupe_ranked_quiz_attempts.php'))->up();
    }

    public function test_doubled_points_are_repaired_keeping_the_first_completed_attempt(): void
    {
        $grade = Grade::factory()->level(3)->create();
        $subject = Subject::factory()->availableIn($grade)->create();
        $chapter = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);
        $quiz = Quiz::factory()->for($chapter)->create();
        $student = User::factory()->student(3)->create(['grade_id' => $grade->id]);

        $later = QuizAttempt::factory()->for($quiz)->ranked()->create([
            'student_id' => $student->id, 'score' => 90, 'max_score' => 100, 'completed_at' => now(),
        ]);
        $first = QuizAttempt::factory()->for($quiz)->ranked()->create([
            'student_id' => $student->id, 'score' => 60, 'max_score' => 100, 'completed_at' => now()->subMinute(),
        ]);
        $open = QuizAttempt::factory()->for($quiz)->ranked()->create([
            'student_id' => $student->id, 'score' => 0, 'max_score' => 100, 'completed_at' => null,
        ]);

        $board = app(LeaderboardService::class);
        $this->assertSame(150, $board->rowFor($student)->points, 'doubled before the repair');

        $this->runRepair();

        $this->assertTrue($first->fresh()->counts_for_ranking);
        $this->assertFalse($later->fresh()->counts_for_ranking);
        $this->assertFalse($open->fresh()->counts_for_ranking);
        $this->assertSame(3, QuizAttempt::where('student_id', $student->id)->count(), 'nothing deleted');
        $this->assertSame(60, $board->rowFor($student)->points);
    }

    public function test_students_without_duplicates_are_untouched_and_it_is_safe_to_rerun(): void
    {
        $quiz = Quiz::factory()->for(Chapter::factory())->create();
        $other = Quiz::factory()->for(Chapter::factory())->create();
        $student = User::factory()->student(3)->create();

        $ranked = QuizAttempt::factory()->for($quiz)->ranked()->create(['student_id' => $student->id, 'completed_at' => now()]);
        $practice = QuizAttempt::factory()->for($quiz)->create(['student_id' => $student->id, 'counts_for_ranking' => false, 'completed_at' => now()]);
        $elsewhere = QuizAttempt::factory()->for($other)->ranked()->create(['student_id' => $student->id, 'completed_at' => now()]);

        $this->runRepair();
        $this->runRepair();

        $this->assertTrue($ranked->fresh()->counts_for_ranking);
        $this->assertFalse($practice->fresh()->counts_for_ranking);
        $this->assertTrue($elsewhere->fresh()->counts_for_ranking);
    }
}
