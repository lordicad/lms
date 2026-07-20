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
}
