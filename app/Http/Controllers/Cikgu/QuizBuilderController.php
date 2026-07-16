<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

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
            'question_type' => $question->question_type,
            'points' => $question->points,
            'options' => $question->options->map(fn (QuestionOption $option) => [
                'option_text' => $option->option_text,
                'is_correct' => (bool) $option->is_correct,
            ])->values()->all(),
        ])->values()->all();

        return view('cikgu.kuiz.soalan', [
            'quiz' => $quiz,
            'chapter' => $quiz->chapter,
            'questions' => $questions,
            'hasAttempts' => $quiz->hasAttempts(),
        ]);
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
        ], [
            'questions.required' => __('Kuiz mesti ada sekurang-kurangnya satu soalan.'),
            'questions.*.question_text.required' => __('Setiap soalan mesti ada teks soalan.'),
            'questions.*.options.min' => __('Setiap soalan mesti ada sekurang-kurangnya :count pilihan jawapan.', ['count' => $config['min_options']]),
            'questions.*.options.max' => __('Setiap soalan tidak boleh melebihi :count pilihan jawapan.', ['count' => $config['max_options']]),
            'questions.*.options.*.option_text.required' => __('Setiap pilihan jawapan mesti diisi.'),
        ]);

        $this->validateCorrectAnswers($validated['questions']);

        // Replace-and-rebuild inside a transaction. Either the whole new question set lands
        // or none of it does; the quiz is never left half-written.
        DB::transaction(function () use ($quiz, $validated) {
            $quiz->questions()->delete();   // options cascade

            foreach ($validated['questions'] as $index => $data) {
                $question = Question::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => $data['question_text'],
                    'question_type' => $data['question_type'],
                    'points' => $data['points'],
                    'sort_order' => $index,
                ]);

                foreach ($data['options'] as $optionIndex => $option) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $option['option_text'],
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
