<?php

namespace Tests\Feature\Student;

use App\Models\Chapter;
use App\Models\Favourite;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterAndAverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unfavouriting_decrements_only_its_own_lesson(): void
    {
        $student = User::factory()->student(3)->create();
        $mine = Lesson::factory()->create(['favourites_count' => 2]);
        $other = Lesson::factory()->create(['favourites_count' => 3]);

        Favourite::create(['student_id' => $student->id, 'lesson_id' => $mine->id]);

        $this->actingAs($student)->deleteJson(route('kegemaran.padam', $mine))->assertOk();

        $this->assertSame(1, $mine->fresh()->favourites_count);
        $this->assertSame(3, $other->fresh()->favourites_count, 'another lesson must not be rewritten');
    }

    /**
     * The counter is unsigned. If it has drifted to 0 while a favourite row still exists,
     * un-favouriting used to fail with "out of range"; it must just stay at 0.
     */
    public function test_unfavouriting_at_zero_does_not_error(): void
    {
        $student = User::factory()->student(3)->create();
        $lesson = Lesson::factory()->create(['favourites_count' => 0]);

        Favourite::create(['student_id' => $student->id, 'lesson_id' => $lesson->id]);

        $this->actingAs($student)->deleteJson(route('kegemaran.padam', $lesson))
            ->assertOk()
            ->assertJson(['favourited' => false, 'count' => 0]);

        $this->assertSame(0, $lesson->fresh()->favourites_count);
    }

    /**
     * "Purata markah" ignores attempts on a quiz whose questions were all deleted (0/0), which
     * read as 0% and dragged the average down.
     */
    public function test_the_average_ignores_quizzes_with_no_questions_left(): void
    {
        $grade = Grade::factory()->level(3)->create();
        $subject = Subject::factory()->availableIn($grade)->create();
        $chapter = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id, 'is_active' => true]);
        $student = User::factory()->student(3)->create(['grade_id' => $grade->id]);

        $real = Quiz::factory()->for($chapter)->create();
        $emptied = Quiz::factory()->for($chapter)->create();

        QuizAttempt::factory()->for($real)->ranked()->create([
            'student_id' => $student->id, 'score' => 80, 'max_score' => 100, 'completed_at' => now(),
        ]);
        QuizAttempt::factory()->for($emptied)->ranked()->create([
            'student_id' => $student->id, 'score' => 0, 'max_score' => 0, 'completed_at' => now(),
        ]);

        $this->actingAs($student)->get(route('kuiz-saya.index'))
            ->assertOk()
            ->assertViewHas('avgScore', 80)
            ->assertViewHas('doneCount', 2);
    }
}
