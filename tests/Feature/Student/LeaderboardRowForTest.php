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
 * rowFor() no longer builds the whole board, so it must still land every student on exactly the
 * rank (and figures) the full ranking() gives them, ties included.
 */
class LeaderboardRowForTest extends TestCase
{
    use RefreshDatabase;

    public function test_row_for_matches_the_full_ranking_for_every_student(): void
    {
        $grade = Grade::factory()->level(5)->create();
        $subjects = Subject::factory()->count(2)->availableIn($grade)->create();
        $quizzes = $subjects->map(function ($subject) use ($grade) {
            $chapter = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);

            return Quiz::factory()->for($chapter)->create();
        });

        // Deliberate collisions: shared points, shared accuracy, and identical finish times, so
        // every tie-break (and the dead-heat fallback) is exercised.
        $scores = [[30, 3, 4], [30, 3, 4], [30, 2, 4], [20, 2, 4], [20, 2, 4], [50, 4, 4], [10, 1, 4], [30, 3, 4]];
        $at = now()->startOfMinute();

        foreach ($scores as $i => [$points, $correct, $questions]) {
            $student = User::factory()->student(5)->create(['grade_id' => $grade->id]);

            foreach ($quizzes as $q => $quiz) {
                QuizAttempt::factory()->for($quiz)->ranked()->create([
                    'student_id' => $student->id,
                    'score' => $points + $q * ($i % 2),
                    'max_score' => 100,
                    'correct_count' => $correct,
                    'question_count' => $questions,
                    'completed_at' => $i % 3 === 0 ? $at : $at->copy()->addMinutes($i),
                ]);
            }
        }

        $board = app(LeaderboardService::class);

        foreach ([null, $subjects[0]->id] as $subjectId) {
            $full = $board->ranking(gradeId: $grade->id, subjectId: $subjectId);

            foreach ($full as $expected) {
                $row = $board->rowFor($expected->student, $subjectId);

                $this->assertSame($expected->rank, $row->rank, "rank for student {$expected->student->id}");
                $this->assertSame($expected->points, $row->points);
                $this->assertSame($expected->accuracy, $row->accuracy);
                $this->assertSame($expected->quizzes, $row->quizzes);
            }
        }
    }

    public function test_a_student_with_no_ranked_attempt_has_no_row(): void
    {
        $student = User::factory()->student(5)->create();

        $this->assertNull(app(LeaderboardService::class)->rowFor($student));
    }
}
