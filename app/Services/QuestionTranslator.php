<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Machine-translates quiz questions between Bahasa Melayu and English using the Anthropic (Claude)
 * API. Detection and translation happen in one call per batch: each item is detected as ms or en
 * and rendered into the other language, with the answer options translated alongside the question
 * so they stay parallel and in order.
 *
 * The whole feature is optional. With no API key configured, enabled() is false and callers skip
 * translation entirely - quizzes save exactly as before, just without a second language.
 */
class QuestionTranslator
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';

    public function enabled(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    /**
     * Translate a batch of questions.
     *
     * @param  array<int, array{question: string, options: array<int, string>}>  $items
     * @return array<int, array{source_locale: string, question: string, options: array<int, string>}>
     *         Aligned by index with the input. Empty array if translation is disabled.
     *
     * @throws RuntimeException on an API or parsing failure, so callers can save without a
     *                          translation rather than losing the quiz.
     */
    public function translate(array $items): array
    {
        if (! $this->enabled() || $items === []) {
            return [];
        }

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(120)->post(self::ENDPOINT, [
            'model' => config('services.anthropic.model'),
            'max_tokens' => 4096,
            'system' => $this->systemPrompt(),
            'messages' => [
                ['role' => 'user', 'content' => $this->userPrompt($items)],
            ],
        ]);

        if ($response->failed()) {
            Log::warning('QuestionTranslator API call failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Translation API returned '.$response->status());
        }

        return $this->parse($response->json('content.0.text', ''), count($items));
    }

    /**
     * Translate a flat list of standalone strings (e.g. a quiz title and description). Each result
     * carries its own detected source language, aligned by index with the input.
     *
     * @param  array<int, string>  $strings
     * @return array<int, array{source_locale: string, text: string}>
     *
     * @throws RuntimeException on an API or parsing failure.
     */
    public function translateStrings(array $strings): array
    {
        $strings = array_values(array_filter($strings, fn ($s) => trim((string) $s) !== ''));

        if (! $this->enabled() || $strings === []) {
            return [];
        }

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(120)->post(self::ENDPOINT, [
            'model' => config('services.anthropic.model'),
            'max_tokens' => 2048,
            'system' => $this->stringsSystemPrompt(),
            'messages' => [
                ['role' => 'user', 'content' => "Translate these strings:\n\n".json_encode(
                    ['strings' => $strings],
                    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                )],
            ],
        ]);

        if ($response->failed()) {
            Log::warning('QuestionTranslator strings call failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Translation API returned '.$response->status());
        }

        return $this->parseStrings($response->json('content.0.text', ''), count($strings));
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
        You translate multiple-choice quiz questions for a Malaysian primary school (Sekolah
        Rendah). Each item is written in either Bahasa Melayu ("ms") or English ("en").

        For every item:
        - Detect the source language.
        - Translate the question text and EACH answer option into the OTHER language.
        - Keep the meaning exact. This is a graded quiz: a mistranslation can turn a correct answer
          wrong. Preserve numbers, units, proper nouns, and subject terms (Sains, Matematik, etc.).
        - Keep the options in the same order as given; do not add, drop, merge, or reorder them.
        - Use natural, grade-appropriate wording a primary-school child would understand.

        Return ONLY a JSON object, no prose and no markdown fences, in exactly this shape:
        {"items":[{"source":"en","question":"...","options":["...","..."]}]}
        where "source" is the detected source language ("ms" or "en"), "question" is the translated
        question, and "options" are the translated options in order. The items array must be the
        same length and order as the input.
        PROMPT;
    }

    private function stringsSystemPrompt(): string
    {
        return <<<'PROMPT'
        You translate short text for a Malaysian primary school learning app (quiz titles and
        descriptions). Each string is in either Bahasa Melayu ("ms") or English ("en").

        For every string:
        - Detect the source language.
        - Translate it into the OTHER language, accurately and in natural, grade-appropriate wording.
        - Preserve numbers, units, and proper nouns.

        Return ONLY a JSON object, no prose and no markdown fences, in exactly this shape:
        {"strings":[{"source":"en","text":"..."}]}
        where "source" is the detected source ("ms" or "en") and "text" is the translation. The
        strings array must be the same length and order as the input.
        PROMPT;
    }

    /**
     * @return array<int, array{source_locale: string, text: string}>
     *
     * @throws RuntimeException
     */
    private function parseStrings(string $text, int $expected): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text) ?? $text;

        $data = json_decode($text, true);

        if (! is_array($data) || ! isset($data['strings']) || ! is_array($data['strings'])) {
            throw new RuntimeException('Translation response was not valid JSON.');
        }

        $out = [];

        foreach ($data['strings'] as $item) {
            $out[] = [
                'source_locale' => ($item['source'] ?? null) === 'en' ? 'en' : 'ms',
                'text' => (string) ($item['text'] ?? ''),
            ];
        }

        if (count($out) !== $expected) {
            throw new RuntimeException('Translation returned '.count($out)." strings, expected {$expected}.");
        }

        return $out;
    }

    /**
     * @param  array<int, array{question: string, options: array<int, string>}>  $items
     */
    private function userPrompt(array $items): string
    {
        $payload = ['items' => array_map(fn ($item) => [
            'question' => $item['question'],
            'options' => array_values($item['options']),
        ], $items)];

        return "Translate these quiz items:\n\n".json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @return array<int, array{source_locale: string, question: string, options: array<int, string>}>
     *
     * @throws RuntimeException
     */
    private function parse(string $text, int $expected): array
    {
        // The model is asked for raw JSON, but strip a ```json fence defensively just in case.
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text) ?? $text;

        $data = json_decode($text, true);

        if (! is_array($data) || ! isset($data['items']) || ! is_array($data['items'])) {
            throw new RuntimeException('Translation response was not valid JSON.');
        }

        $out = [];

        foreach ($data['items'] as $item) {
            $source = ($item['source'] ?? null) === 'en' ? 'en' : 'ms';

            $out[] = [
                'source_locale' => $source,
                'question' => (string) ($item['question'] ?? ''),
                'options' => array_map('strval', array_values((array) ($item['options'] ?? []))),
            ];
        }

        if (count($out) !== $expected) {
            throw new RuntimeException('Translation returned '.count($out)." items, expected {$expected}.");
        }

        return $out;
    }
}
