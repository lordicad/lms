<?php

namespace Tests\Feature\Student;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    private function quizInGrade(int $level): Quiz
    {
        $grade = Grade::factory()->level($level)->create();
        $subject = Subject::factory()->availableIn($grade)->create();
        $chapter = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);

        return Quiz::factory()->for($chapter)->create();
    }

    public function test_leaderboard_is_capped_at_100_and_pins_the_students_own_row(): void
    {
        $quiz = $this->quizInGrade(3);

        // 105 ranked students in Tahun 3, descending scores so ranks are deterministic.
        foreach (range(1, 105) as $i) {
            $student = User::factory()->student(3)->create();
            QuizAttempt::factory()->for($quiz)->ranked()->create([
                'student_id' => $student->id,
                'score' => 1000 - $i,
                'max_score' => 1000,
                'completed_at' => now(),
            ]);
        }

        // The acting student scores lowest, so they fall outside the Top 100.
        $me = User::factory()->student(3)->create();
        QuizAttempt::factory()->for($quiz)->ranked()->create([
            'student_id' => $me->id, 'score' => 1, 'max_score' => 1000, 'completed_at' => now(),
        ]);

        $response = $this->actingAs($me)->get(route('ranking.index'));

        $response->assertOk();
        $this->assertCount(100, $response->viewData('top'));
        $this->assertTrue($response->viewData('showMyRow'));
        $this->assertSame(106, $response->viewData('myRow')->rank);
    }

    public function test_leaderboard_does_not_expose_students_from_another_year(): void
    {
        $quiz3 = $this->quizInGrade(3);
        $quiz4 = $this->quizInGrade(4);

        $mine = User::factory()->student(3)->create(['name' => 'Tahun Tiga Murid']);
        QuizAttempt::factory()->for($quiz3)->ranked()->create([
            'student_id' => $mine->id, 'score' => 500, 'max_score' => 1000, 'completed_at' => now(),
        ]);

        $other = User::factory()->student(4)->create(['name' => 'Tahun Empat Murid']);
        QuizAttempt::factory()->for($quiz4)->ranked()->create([
            'student_id' => $other->id, 'score' => 900, 'max_score' => 1000, 'completed_at' => now(),
        ]);

        $me = User::factory()->student(3)->create();
        $response = $this->actingAs($me)->get(route('ranking.index'));

        $response->assertOk();
        $ids = $response->viewData('top')->pluck('student.id')->all();
        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    /**
     * The app lets a student switch Tahun to revise a lower year; those attempts are still graded,
     * but they must not earn points on the student's home-year board - otherwise easy out-of-year
     * quizzes farm a higher standing than peers who only did their own year's work.
     */
    public function test_out_of_year_revision_attempts_do_not_inflate_the_home_year_board(): void
    {
        $quiz6 = $this->quizInGrade(6);
        $quiz1 = $this->quizInGrade(1);

        // Two Tahun 6 students do the same Tahun 6 quiz, same score.
        $honest = User::factory()->student(6)->create(['name' => 'Aina']);
        $farmer = User::factory()->student(6)->create(['name' => 'Bara']);
        foreach ([$honest, $farmer] as $student) {
            QuizAttempt::factory()->for($quiz6)->ranked()->create([
                'student_id' => $student->id, 'score' => 50, 'max_score' => 100, 'completed_at' => now(),
            ]);
        }

        // The farmer also grinds an easy Tahun 1 quiz - a ranked first attempt worth 100.
        QuizAttempt::factory()->for($quiz1)->ranked()->create([
            'student_id' => $farmer->id, 'score' => 100, 'max_score' => 100, 'completed_at' => now(),
        ]);

        $board = app(\App\Services\LeaderboardService::class)->ranking(gradeId: $honest->grade_id)
            ->keyBy(fn ($row) => $row->student->id);

        // Only the Tahun 6 quiz counts: both students sit at 50 on one quiz, the revision does not add.
        $this->assertSame(50, $board[$farmer->id]->points, 'the Tahun 1 revision quiz inflated the Tahun 6 board');
        $this->assertSame(1, $board[$farmer->id]->quizzes);
        $this->assertSame(50, $board[$honest->id]->points);
    }
}
