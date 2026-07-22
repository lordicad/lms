<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Rules\ValidSubjectGradeCombo;
use App\Support\Uploads;
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
        $teacher = $this->teacher($request);

        if (! $teacher) {
            return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
        }

        $data = $this->validatedPayload($request);
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

            $this->replaceQuestions($quiz, $data['questions']);

            return $quiz;
        });

        return response()->json(['id' => $quiz->id], 201);
    }

    /**
     * A printable "kuiz fail": the teacher uploads a document students download instead of
     * answering in-app. Mirrors the web Cikgu\QuizController@store for TYPE_FILE and reuses the
     * same QuizRequest limits (config lms.quiz_file_mimes / quiz_file_max_mb).
     */
    public function storeFile(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);

        if (! $teacher) {
            return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
        }

        $max = (int) config('lms.quiz_file_max_mb') * 1024; // validator wants kilobytes

        $data = $request->validate([
            'chapter_id' => ['required', 'integer', Rule::exists('chapters', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', config('lms.quiz_file_mimes')),
                "max:{$max}",
            ],
            'is_published' => ['boolean'],
        ], [
            'chapter_id.required' => __('Sila pilih Subjek, Tahun dan Bab.'),
            'title.required' => __('Sila isi tajuk kuiz.'),
            'file.required' => __('Sila pilih fail kuiz.'),
            'file.mimes' => __('Fail kuiz mesti PDF, DOC atau DOCX.'),
            'file.max' => __('Saiz fail terlalu besar. Had ialah :max MB.', ['max' => config('lms.quiz_file_max_mb')]),
        ]);

        $file = $request->file('file');

        $quiz = Quiz::create([
            'chapter_id' => $data['chapter_id'],
            'teacher_id' => $teacher->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => Quiz::TYPE_FILE,
            'duration_minutes' => null, // only meaningful for interactive quizzes
            'file_path' => Uploads::store($file, 'quizzes'),
            'original_name' => $file->getClientOriginalName(),
            'is_published' => $data['is_published'] ?? true,
        ]);

        return response()->json(['id' => $quiz->id], 201);
    }

    /**
     * The complete interactive quiz payload for the mobile editor.
     */
    public function show(Request $request, Quiz $quiz): JsonResponse
    {
        if (! $this->ownsInteractiveQuiz($request, $quiz)) {
            return $this->notFoundOrForbidden($request, $quiz);
        }

        $quiz->load('chapter.subject', 'chapter.grade', 'questions.options')
            ->loadCount(['completedAttempts as attempts_count']);

        return response()->json([
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'chapter_id' => $quiz->chapter_id,
                'subject_id' => $quiz->chapter?->subject_id,
                'grade_id' => $quiz->chapter?->grade_id,
                'duration_minutes' => $quiz->duration_minutes,
                'published' => (bool) $quiz->is_published,
                'attempts' => (int) $quiz->attempts_count,
                'questions' => $quiz->questions->map(fn (Question $question) => [
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'points' => $question->points,
                    'options' => $question->options->map(fn (QuestionOption $option) => [
                        'option_text' => $option->option_text,
                        'is_correct' => (bool) $option->is_correct,
                    ])->values()->all(),
                ])->values()->all(),
            ],
        ]);
    }

    /**
     * Rebuilds an interactive quiz atomically. Historical attempt scores remain, but their
     * old answer rows follow the deleted questions, matching the warning shown in the web app.
     */
    public function update(Request $request, Quiz $quiz): JsonResponse
    {
        if (! $this->ownsInteractiveQuiz($request, $quiz)) {
            return $this->notFoundOrForbidden($request, $quiz);
        }

        $data = $this->validatedPayload($request);
        $this->validateCorrectAnswers($data['questions']);

        DB::transaction(function () use ($quiz, $data) {
            $quiz->update([
                'chapter_id' => $data['chapter_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'is_published' => $data['is_published'] ?? true,
            ]);

            $quiz->questions()->delete();
            $this->replaceQuestions($quiz, $data['questions']);
        });

        return response()->json(['id' => $quiz->id]);
    }

    /**
     * Per-quiz performance for the teacher: summary, question accuracy and recent attempts.
     */
    public function stats(Request $request, Quiz $quiz): JsonResponse
    {
        if (! $this->ownsInteractiveQuiz($request, $quiz)) {
            return $this->notFoundOrForbidden($request, $quiz);
        }

        $quiz->load('questions');
        $summary = $quiz->completedAttempts()
            ->selectRaw('COUNT(*) as completed_count, AVG(score) as average_score, AVG(CASE WHEN max_score > 0 THEN score * 100.0 / max_score ELSE 0 END) as average_percent')
            ->first();
        $completedCount = (int) ($summary?->completed_count ?? 0);

        $perQuestion = DB::table('attempt_answers')
            ->join('quiz_attempts', 'quiz_attempts.id', '=', 'attempt_answers.quiz_attempt_id')
            ->where('quiz_attempts.quiz_id', $quiz->id)
            ->whereNotNull('quiz_attempts.completed_at')
            ->groupBy('attempt_answers.question_id')
            ->select([
                'attempt_answers.question_id',
                DB::raw('COUNT(*) as answered'),
                DB::raw('SUM(attempt_answers.is_correct) as correct'),
            ])
            ->get()
            ->keyBy('question_id');

        $attempts = $quiz->completedAttempts()
            ->with('student.grade')
            ->orderByDesc('completed_at')
            ->limit(100)
            ->get();

        return response()->json([
            'stats' => [
                'completed_count' => $completedCount,
                'max_score' => $quiz->maxScore(),
                'average_score' => round((float) ($summary?->average_score ?? 0), 1),
                'average_percent' => (int) round((float) ($summary?->average_percent ?? 0)),
                'questions' => $quiz->questions->values()->map(function (Question $question, int $index) use ($perQuestion) {
                    $stat = $perQuestion->get($question->id);
                    $answered = (int) ($stat->answered ?? 0);
                    $correct = (int) ($stat->correct ?? 0);

                    return [
                        'number' => $index + 1,
                        'question_text' => $question->question_text,
                        'answered' => $answered,
                        'correct' => $correct,
                        'rate' => $answered > 0 ? (int) round($correct / $answered * 100) : 0,
                    ];
                })->all(),
                'attempts' => $attempts->map(fn (QuizAttempt $attempt) => [
                    'student_name' => $attempt->student?->name,
                    'grade_name' => $attempt->student?->grade?->name,
                    'score' => $attempt->score,
                    'max_score' => $attempt->max_score,
                    'percent' => $attempt->percentage(),
                    'correct_count' => $attempt->correct_count,
                    'question_count' => $attempt->question_count,
                    'duration' => $attempt->humanDuration(),
                    'counts_for_ranking' => (bool) $attempt->counts_for_ranking,
                    'completed_at' => $attempt->completed_at?->toIso8601String(),
                ])->all(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request): array
    {
        $config = config('lms.quiz');

        return $request->validate([
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
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function replaceQuestions(Quiz $quiz, array $questions): void
    {
        foreach ($questions as $index => $questionData) {
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
    }

    private function teacher(Request $request): ?User
    {
        $user = $request->user();

        return $user && $user->isTeacher() ? $user : null;
    }

    private function ownsInteractiveQuiz(Request $request, Quiz $quiz): bool
    {
        $teacher = $this->teacher($request);

        return $teacher && $quiz->teacher_id === $teacher->id && $quiz->isInteractive();
    }

    private function notFoundOrForbidden(Request $request, Quiz $quiz): JsonResponse
    {
        $teacher = $this->teacher($request);

        if (! $teacher || $quiz->teacher_id !== $teacher->id) {
            return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
        }

        return response()->json(['message' => 'Hanya kuiz interaktif boleh dibuka di aplikasi.'], 422);
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
