<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Rules\ValidSubjectGradeCombo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Creates an entire interactive quiz from the mobile question builder.
 *
 * Questions and options are sent as one payload and persisted in one database
 * transaction. A teacher never leaves a half-built quiz visible to students.
 */
class QuizController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $teacher = $request->user();

        if (! $teacher || ! $teacher->isTeacher()) {
            return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
        }

        $config = config('lms.quiz');

        $data = $request->validate([
            'chapter_id' => [
                'required',
                'integer',
                Rule::exists('chapters', 'id'),
                ValidSubjectGradeCombo::forChapter(),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'is_published' => ['boolean'],
            'questions' => ['required', 'array', 'min:1', 'max:'.$config['max_questions']],
            'questions.*.question_text' => ['required', 'string', 'max:2000'],
            'questions.*.question_type' => [
                'required',
                Rule::in([Question::TYPE_SINGLE, Question::TYPE_MULTIPLE]),
            ],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.options' => [
                'required',
                'array',
                "min:{$config['min_options']}",
                "max:{$config['max_options']}",
            ],
            'questions.*.options.*.option_text' => ['required', 'string', 'max:500'],
            'questions.*.options.*.is_correct' => ['required', 'boolean'],
        ], [
            'chapter_id.required' => 'Sila pilih Subjek, Tahun dan Bab.',
            'title.required' => 'Sila isi tajuk kuiz.',
            'questions.required' => 'Kuiz mesti ada sekurang-kurangnya satu soalan.',
            'questions.*.question_text.required' => 'Setiap soalan mesti ada teks soalan.',
            'questions.*.options.min' => 'Setiap soalan mesti ada sekurang-kurangnya dua pilihan jawapan.',
            'questions.*.options.*.option_text.required' => 'Setiap pilihan jawapan mesti diisi.',
        ]);

        $this->validateCorrectAnswers($data['questions']);

        $quiz = DB::transaction(function () use ($data, $teacher) {
            $quiz = Quiz::create([
                'chapter_id' => $data['chapter_id'],
                'teacher_id' => $teacher->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => Quiz::TYPE_INTERACTIVE,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'is_published' => $data['is_published'] ?? true,
            ]);

            foreach ($data['questions'] as $index => $questionData) {
                $question = Question::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => $questionData['question_text'],
                    'question_type' => $questionData['question_type'],
                    'points' => $questionData['points'],
                    'sort_order' => $index,
                ]);

                foreach ($questionData['options'] as $optionIndex => $optionData) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $optionData['option_text'],
                        'is_correct' => (bool) $optionData['is_correct'],
                        'sort_order' => $optionIndex,
                    ]);
                }
            }

            return $quiz;
        });

        return response()->json(['id' => $quiz->id], 201);
    }

    /**
     * Radio questions have exactly one right answer; checkbox questions have at least one.
     * This is verified by the server instead of trusting the mobile controls.
     *
     * @param array<int, array<string, mixed>> $questions
     */
    private function validateCorrectAnswers(array $questions): void
    {
        $errors = [];

        foreach ($questions as $index => $question) {
            $correct = collect($question['options'])
                ->filter(fn (array $option) => (bool) $option['is_correct'])
                ->count();
            $number = $index + 1;

            if ($question['question_type'] === Question::TYPE_SINGLE && $correct !== 1) {
                $errors["questions.{$index}.options"] =
                    "Soalan {$number} mesti mempunyai tepat satu jawapan betul.";
            }

            if ($question['question_type'] === Question::TYPE_MULTIPLE && $correct < 1) {
                $errors["questions.{$index}.options"] =
                    "Soalan {$number} mesti mempunyai sekurang-kurangnya satu jawapan betul.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
