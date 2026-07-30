<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Services\QuestionTranslator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class QuizBuilderController extends Controller
{
    /**
     * The Google-Forms-style builder. Questions are edited entirely in the browser (Alpine)
     * and posted back as one JSON payload, so a half-saved quiz is not possible.
     */
    public function edit(Quiz $quiz): View
    {
        $this->authorize('update', $quiz);

        abort_unless($quiz->isInteractive(), Response::HTTP_NOT_FOUND);

        $quiz->load('chapter.subject', 'chapter.grade', 'questions.options');

        $questions = $quiz->questions->map(fn (Question $question) => [
            'question_text' => $question->question_text,
            'source_locale' => $question->source_locale,
            'question_text_translated' => $question->question_text_translated,
            'question_type' => $question->question_type,
            'points' => $question->points,
            'options' => $question->options->map(fn (QuestionOption $option) => [
                'option_text' => $option->option_text,
                'option_text_translated' => $option->option_text_translated,
                'is_correct' => (bool) $option->is_correct,
            ])->values()->all(),
        ])->values()->all();

        return view('cikgu.kuiz.soalan', [
            'quiz' => $quiz,
            'chapter' => $quiz->chapter,
            'questions' => $questions,
            'hasAttempts' => $quiz->hasAttempts(),
            'translatorEnabled' => app(QuestionTranslator::class)->enabled(),
        ]);
    }

    /**
     * Leaving the builder. An interactive quiz with no questions is unusable — a teacher who
     * created one and then backed out without adding any never really meant to keep it — so it is
     * discarded rather than left cluttering the list. A quiz that already holds questions (an edit
     * that was cancelled) is untouched.
     */
    public function cancel(Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        abort_unless($quiz->isInteractive(), Response::HTTP_NOT_FOUND);

        if ($quiz->questions()->doesntExist()) {
            $quiz->delete();

            return redirect()
                ->route('cikgu.kuiz.index')
                ->with('status', __('Kuiz dibuang kerana tiada soalan ditambah.'));
        }

        return redirect()->route('cikgu.kuiz.index');
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        abort_unless($quiz->isInteractive(), Response::HTTP_NOT_FOUND);

        $config = config('lms.quiz');

        $validated = $request->validate([
            'questions' => ['required', 'array', 'min:1', 'max:'.$config['max_questions']],
            'questions.*.question_text' => ['required', 'string', 'max:2000'],
            'questions.*.question_type' => ['required', Rule::in([Question::TYPE_SINGLE, Question::TYPE_MULTIPLE])],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.options' => ['required', 'array', "min:{$config['min_options']}", "max:{$config['max_options']}"],
            'questions.*.options.*.option_text' => ['required', 'string', 'max:500'],
            'questions.*.options.*.is_correct' => ['required', 'boolean'],
            // Optional translation fields the builder submits after an auto-translate + review.
            'questions.*.source_locale' => ['nullable', Rule::in(['ms', 'en'])],
            'questions.*.question_text_translated' => ['nullable', 'string', 'max:2000'],
            'questions.*.options.*.option_text_translated' => ['nullable', 'string', 'max:500'],
        ], [
            'questions.required' => __('Kuiz mesti ada sekurang-kurangnya satu soalan.'),
            'questions.*.question_text.required' => __('Setiap soalan mesti ada teks soalan.'),
            'questions.*.options.min' => __('Setiap soalan mesti ada sekurang-kurangnya :count pilihan jawapan.', ['count' => $config['min_options']]),
            'questions.*.options.max' => __('Setiap soalan tidak boleh melebihi :count pilihan jawapan.', ['count' => $config['max_options']]),
            'questions.*.options.*.option_text.required' => __('Setiap pilihan jawapan mesti diisi.'),
        ]);

        $this->validateCorrectAnswers($validated['questions']);

        // Fill in any translation the teacher didn't already provide (e.g. never clicked the
        // auto-translate button). Best-effort: if the API is down, questions save untranslated.
        $questions = $this->withTranslations($validated['questions']);

        // Replace-and-rebuild inside a transaction. Either the whole new question set lands
        // or none of it does; the quiz is never left half-written.
        DB::transaction(function () use ($quiz, $questions) {
            $quiz->questions()->delete();   // options cascade

            foreach ($questions as $index => $data) {
                $question = Question::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => $data['question_text'],
                    'source_locale' => $data['source_locale'] ?? null,
                    'question_text_translated' => $data['question_text_translated'] ?? null,
                    'question_type' => $data['question_type'],
                    'points' => $data['points'],
                    'sort_order' => $index,
                ]);

                foreach ($data['options'] as $optionIndex => $option) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $option['option_text'],
                        'option_text_translated' => $option['option_text_translated'] ?? null,
                        'is_correct' => (bool) $option['is_correct'],
                        'sort_order' => $optionIndex,
                    ]);
                }
            }
        });

        return redirect()
            ->route('cikgu.kuiz.index')
            ->with('status', __('Soalan kuiz berjaya disimpan.'));
    }

    /**
     * Live translation for the builder's "Terjemah automatik" button: takes the questions as they
     * stand in the browser and returns their other-language version for the teacher to review and
     * edit before saving. Kept separate from update() so the teacher sees the result first.
     */
    public function translate(Request $request, Quiz $quiz, QuestionTranslator $translator): JsonResponse
    {
        $this->authorize('update', $quiz);

        if (! $translator->enabled()) {
            return response()->json(['message' => __('Terjemahan automatik tidak tersedia.')], 422);
        }

        $validated = $request->validate([
            'questions' => ['required', 'array', 'min:1', 'max:100'],
            'questions.*.question_text' => ['required', 'string', 'max:2000'],
            'questions.*.options' => ['required', 'array', 'min:1'],
            'questions.*.options.*.option_text' => ['required', 'string', 'max:500'],
        ]);

        try {
            $results = $translator->translate($this->toItems($validated['questions']));
        } catch (Throwable $e) {
            Log::warning('Quiz auto-translate failed', ['quiz' => $quiz->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => __('Terjemahan gagal. Sila cuba lagi.')], 502);
        }

        return response()->json(['items' => $results]);
    }

    /**
     * Merge auto-translations into the saved question set for any question that doesn't already
     * carry one. Best-effort: on any API failure the questions pass through untranslated so the
     * save still succeeds.
     *
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    private function withTranslations(array $questions): array
    {
        $translator = app(QuestionTranslator::class);

        if (! $translator->enabled()) {
            return $questions;
        }

        // Only translate questions still missing a translation, so re-saving an already-translated
        // quiz (or one the teacher reviewed via the button) costs nothing.
        $pending = [];

        foreach ($questions as $index => $data) {
            if ($this->needsTranslation($data)) {
                $pending[$index] = $data;
            }
        }

        if ($pending === []) {
            return $questions;
        }

        try {
            $results = $translator->translate($this->toItems($pending));
        } catch (Throwable $e) {
            Log::warning('Quiz auto-translate on save failed', ['error' => $e->getMessage()]);

            return $questions;
        }

        // $results is aligned by position with $pending's values; map back to original indices.
        foreach (array_keys($pending) as $position => $index) {
            $result = $results[$position] ?? null;

            if (! $result) {
                continue;
            }

            $questions[$index]['source_locale'] = $result['source_locale'];
            $questions[$index]['question_text_translated'] = $result['question'];

            foreach ($questions[$index]['options'] as $optionIndex => $option) {
                $questions[$index]['options'][$optionIndex]['option_text_translated'] = $result['options'][$optionIndex] ?? null;
            }
        }

        return $questions;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function needsTranslation(array $data): bool
    {
        if (empty($data['source_locale']) || empty($data['question_text_translated'])) {
            return true;
        }

        foreach ($data['options'] as $option) {
            if (empty($option['option_text_translated'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reduce the builder payload to the shape the translator wants.
     *
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array{question: string, options: array<int, string>}>
     */
    private function toItems(array $questions): array
    {
        return array_map(fn ($data) => [
            'question' => $data['question_text'],
            'options' => array_map(fn ($option) => $option['option_text'], array_values($data['options'])),
        ], array_values($questions));
    }

    /**
     * A radio question has exactly one right answer, a checkbox question at least one.
     * Enforced here rather than trusted from the browser, since the payload is user input.
     *
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function validateCorrectAnswers(array $questions): void
    {
        $errors = [];

        foreach ($questions as $index => $question) {
            $correct = collect($question['options'])->where('is_correct', true)->count();
            $position = $index + 1;

            if ($question['question_type'] === Question::TYPE_SINGLE && $correct !== 1) {
                $errors["questions.{$index}.options"] =
                    __('Soalan :number: soalan jenis radio mesti ada tepat SATU jawapan betul (sekarang: :count).', ['number' => $position, 'count' => $correct]);
            }

            if ($question['question_type'] === Question::TYPE_MULTIPLE && $correct < 1) {
                $errors["questions.{$index}.options"] =
                    __('Soalan :number: soalan jenis checkbox mesti ada sekurang-kurangnya satu jawapan betul.', ['number' => $position]);
            }
        }

        if ($errors !== []) {
            /** @var Validator $validator */
            $validator = validator([], []);

            foreach ($errors as $key => $message) {
                $validator->errors()->add($key, $message);
            }

            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }
}
