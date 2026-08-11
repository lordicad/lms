<?php

namespace App\Services;

use App\Models\AnswerExplanation;
use App\Models\AttemptAnswer;
use App\Models\Question;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Generates a short, kid-friendly explanation of why a quiz question's correct answer is right (and
 * why the student's pick was wrong), using the Anthropic (Claude) API. Shown on demand on the quiz
 * review page, next to answers the student got wrong.
 *
 * Optional, exactly like {@see QuestionTranslator}: with no API key configured, enabled() is false
 * and the review page simply never offers the button.
 *
 * Results are cached in answer_explanations, so Claude is called once per distinct
 * (question, locale, chosen-wrong-answer) and reused for every later view.
 */
class AnswerExplainer
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';

    public function enabled(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    /**
     * Explain the given (wrong) attempt answer in the reader's language, from cache when possible.
     *
     * @throws RuntimeException on an API or empty-response failure, so the caller can surface a
     *                          "try again" message rather than a broken page.
     */
    public function explain(AttemptAnswer $answer, string $locale): string
    {
        $locale = $locale === 'en' ? 'en' : 'ms';

        $question = $answer->question;
        $question->loadMissing('options');

        $selected = array_map('intval', $answer->selected_option_ids ?? []);
        $key = AnswerExplanation::keyFor($selected);

        $cached = AnswerExplanation::where('question_id', $question->id)
            ->where('locale', $locale)
            ->where('answer_key', $key)
            ->first();

        if ($cached) {
            return $cached->explanation;
        }

        $explanation = $this->generate($question, $selected, $locale);

        // Race-safe: if two students trigger the same question at once, keep the first row.
        AnswerExplanation::firstOrCreate(
            ['question_id' => $question->id, 'locale' => $locale, 'answer_key' => $key],
            ['explanation' => $explanation],
        );

        return $explanation;
    }

    /**
     * @param  array<int, int>  $selectedIds
     *
     * @throws RuntimeException
     */
    private function generate(Question $question, array $selectedIds, string $locale): string
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Answer explanations are not available.');
        }

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(60)->post(self::ENDPOINT, [
            'model' => config('services.anthropic.model'),
            'max_tokens' => 512,
            'system' => $this->systemPrompt($locale),
            'messages' => [
                ['role' => 'user', 'content' => $this->userPrompt($question, $selectedIds, $locale)],
            ],
        ]);

        if ($response->failed()) {
            Log::warning('AnswerExplainer API call failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Explanation API returned '.$response->status());
        }

        $text = trim($response->json('content.0.text', ''));

        if ($text === '') {
            throw new RuntimeException('Explanation API returned an empty response.');
        }

        return $text;
    }

    private function systemPrompt(string $locale): string
    {
        $language = $locale === 'en' ? 'English' : 'Bahasa Melayu';

        return <<<PROMPT
        You are a friendly, encouraging teacher for a Malaysian primary school (Sekolah Rendah).
        A pupil answered a multiple-choice quiz question wrongly. Explain, so a young child can
        understand:
        - why the correct answer is correct, and
        - why the answer the pupil chose is wrong.

        Rules:
        - Write ONLY in {$language}.
        - Keep it short: 2 to 4 simple sentences. No headings, no bullet points, no markdown.
        - Be warm and encouraging, never make the child feel bad.
        - Use grade-appropriate words and, where it helps, one tiny everyday example.
        - Do not repeat the full question text back; go straight to the explanation.
        PROMPT;
    }

    /**
     * @param  array<int, int>  $selectedIds
     */
    private function userPrompt(Question $question, array $selectedIds, string $locale): string
    {
        $lines = ['Question: '.$question->localizedText(), '', 'Options:'];

        foreach ($question->options as $option) {
            $marks = [];
            if ($option->is_correct) {
                $marks[] = 'CORRECT ANSWER';
            }
            if (in_array((int) $option->id, $selectedIds, true)) {
                $marks[] = "PUPIL'S CHOICE";
            }

            $suffix = $marks ? '  <- '.implode(', ', $marks) : '';
            $lines[] = $option->letter().'. '.$option->localizedText($question->source_locale).$suffix;
        }

        $language = $locale === 'en' ? 'English' : 'Bahasa Melayu';
        $lines[] = '';
        $lines[] = "Explain in {$language} why the correct answer is right and why the pupil's choice is wrong.";

        return implode("\n", $lines);
    }
}
