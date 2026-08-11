<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rewriting a quiz's questions replaces them with new ids. An in-flight (unsubmitted) attempt was
 * served the old questions, so grading it against the new set on submit would score a bogus 0 and
 * lock it onto the leaderboard. Editing clears those open attempts; completed attempts keep their
 * scores, matching the warning the builder already shows.
 */
class QuizEditClearsInFlightAttemptsTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_questions_clears_open_attempts_but_keeps_completed_ones(): void
    {
        $teacher = User::factory()->teacher()->create();
        $quiz = Quiz::create([
            'chapter_id' => Chapter::factory()->create()->id,
            'teacher_id' => $teacher->id,
            'title' => 'Kuiz',
            'type' => Quiz::TYPE_INTERACTIVE,
            'is_published' => true,
        ]);
        Question::create([
            'quiz_id' => $quiz->id, 'question_text' => 'Lama?',
            'question_type' => Question::TYPE_SINGLE, 'points' => 10, 'sort_order' => 0,
        ]);

        // One student is mid-quiz (open, ranked); another already finished with a real score.
        $open = QuizAttempt::factory()->for($quiz)->ranked()->incomplete()->create([
            'student_id' => User::factory()->student(3)->create()->id,
        ]);
        $done = QuizAttempt::factory()->for($quiz)->passed()->create([
            'student_id' => User::factory()->student(3)->create()->id,
            'score' => 90, 'max_score' => 100,
        ]);

        $this->actingAs($teacher)->put(route('cikgu.kuiz.soalan.simpan', $quiz), [
            'questions' => [[
                'question_text' => 'Baharu?',
                'question_type' => Question::TYPE_SINGLE,
                'points' => 10,
                'options' => [
                    ['option_text' => 'A', 'is_correct' => true],
                    ['option_text' => 'B', 'is_correct' => false],
                ],
            ]],
        ])->assertRedirect(route('cikgu.kuiz.index'))->assertSessionHasNoErrors();

        // The open attempt is gone, so it can never be submitted into a bogus ranked 0.
        $this->assertNull($open->fresh(), 'the in-flight attempt should be cleared on edit');
        // The completed attempt and its score are untouched.
        $this->assertDatabaseHas('quiz_attempts', ['id' => $done->id, 'score' => 90]);
    }
}
