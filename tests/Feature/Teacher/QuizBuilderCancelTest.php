<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cancelling out of the question builder.
 *
 * An interactive quiz is created before the builder opens, so backing out without adding any
 * questions would otherwise leave an unusable empty quiz behind. Cancel discards that draft, but
 * never a quiz that already holds questions.
 */
class QuizBuilderCancelTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Chapter $chapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->teacher()->create();
        $this->chapter = Chapter::factory()->create();
    }

    private function quiz(): Quiz
    {
        return Quiz::create([
            'chapter_id' => $this->chapter->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Kuiz Baharu',
            'type' => Quiz::TYPE_INTERACTIVE,
            'is_published' => true,
        ]);
    }

    public function test_cancelling_an_empty_quiz_discards_it(): void
    {
        $quiz = $this->quiz();

        $this->actingAs($this->teacher)
            ->delete(route('cikgu.kuiz.soalan.batal', $quiz))
            ->assertRedirect(route('cikgu.kuiz.index'));

        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
    }

    public function test_cancelling_a_quiz_with_questions_keeps_it(): void
    {
        $quiz = $this->quiz();
        Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Soalan?',
            'question_type' => Question::TYPE_SINGLE,
            'points' => 10,
            'sort_order' => 0,
        ]);

        $this->actingAs($this->teacher)
            ->delete(route('cikgu.kuiz.soalan.batal', $quiz))
            ->assertRedirect(route('cikgu.kuiz.index'));

        $this->assertDatabaseHas('quizzes', ['id' => $quiz->id]);
    }

    public function test_another_teacher_cannot_cancel_someone_elses_quiz(): void
    {
        $quiz = $this->quiz();
        $other = User::factory()->teacher()->create();

        $this->actingAs($other)
            ->delete(route('cikgu.kuiz.soalan.batal', $quiz))
            ->assertForbidden();

        $this->assertDatabaseHas('quizzes', ['id' => $quiz->id]);
    }
}
