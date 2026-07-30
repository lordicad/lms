<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Automatic BM⇄EN translation of quiz questions.
 *
 * The Anthropic API key is unset in the test environment, so the translator no-ops and the builder
 * simply persists whatever translation fields the browser submitted (as it does after a teacher
 * reviews an auto-translation). These tests cover that persistence and the locale-aware accessors
 * the student pages read — without any network call.
 */
class QuizTranslationTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->teacher()->create();
        $chapter = Chapter::factory()->create();
        $this->quiz = Quiz::create([
            'chapter_id' => $chapter->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Kuiz',
            'type' => Quiz::TYPE_INTERACTIVE,
            'is_published' => true,
        ]);
    }

    public function test_the_builder_persists_the_reviewed_translation(): void
    {
        $payload = ['questions' => [[
            'question_text' => 'What is 2 + 2?',
            'source_locale' => 'en',
            'question_text_translated' => 'Apakah 2 + 2?',
            'question_type' => 'single',
            'points' => 10,
            'options' => [
                ['option_text' => 'Four', 'option_text_translated' => 'Empat', 'is_correct' => 1],
                ['option_text' => 'Five', 'option_text_translated' => 'Lima', 'is_correct' => 0],
            ],
        ]]];

        $this->actingAs($this->teacher)
            ->put(route('cikgu.kuiz.soalan.simpan', $this->quiz), $payload)
            ->assertRedirect(route('cikgu.kuiz.index'));

        $this->assertDatabaseHas('questions', [
            'quiz_id' => $this->quiz->id,
            'question_text' => 'What is 2 + 2?',
            'source_locale' => 'en',
            'question_text_translated' => 'Apakah 2 + 2?',
        ]);
        $this->assertDatabaseHas('question_options', [
            'option_text' => 'Four',
            'option_text_translated' => 'Empat',
        ]);
    }

    public function test_question_text_follows_the_locale(): void
    {
        $question = Question::create([
            'quiz_id' => $this->quiz->id,
            'question_text' => 'What is 2 + 2?',
            'source_locale' => 'en',
            'question_text_translated' => 'Apakah 2 + 2?',
            'question_type' => Question::TYPE_SINGLE,
            'points' => 10,
            'sort_order' => 0,
        ]);

        app()->setLocale('ms');
        $this->assertSame('Apakah 2 + 2?', $question->localizedText());

        app()->setLocale('en');
        $this->assertSame('What is 2 + 2?', $question->localizedText());
    }

    public function test_option_text_follows_the_locale(): void
    {
        $option = new QuestionOption([
            'option_text' => 'Four',
            'option_text_translated' => 'Empat',
        ]);

        app()->setLocale('ms');
        $this->assertSame('Empat', $option->localizedText('en'));

        app()->setLocale('en');
        $this->assertSame('Four', $option->localizedText('en'));
    }

    public function test_untranslated_question_shows_the_original_in_both_languages(): void
    {
        $question = Question::create([
            'quiz_id' => $this->quiz->id,
            'question_text' => 'Soalan asal',
            'source_locale' => null,
            'question_text_translated' => null,
            'question_type' => Question::TYPE_SINGLE,
            'points' => 10,
            'sort_order' => 0,
        ]);

        app()->setLocale('en');
        $this->assertSame('Soalan asal', $question->localizedText());

        app()->setLocale('ms');
        $this->assertSame('Soalan asal', $question->localizedText());
    }
}
