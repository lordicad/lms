<?php

namespace App\Http\Controllers\Api\Student;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizGrader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interactive quiz flow for the mobile app: cover (intro), start/resume an attempt,
 * submit answers, and read a graded result. Mirrors the web QuizAttemptController as
 * JSON and reuses QuizGrader, so scoring and ranking rules stay identical to the web.
 * Correct answers are never sent to the client before the attempt is graded.
 */
class QuizController extends StudentApiController
{
    public function __construct(private readonly QuizGrader $grader) {}

    public function intro(Request $request, Quiz $quiz): JsonResponse
    {
        $user = $request->user();

        if (! $quiz->is_published) {
            return response()->json(['message' => 'Kuiz tidak tersedia.'], 404);
        }

        $quiz->load('chapter.subject', 'chapter.grade', 'teacher');

        $myAttempts = $user->isStudent()
            ? $quiz->attempts()->where('student_id', $user->id)->completed()->latest('completed_at')->get()
            : collect();

        return response()->json([
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'type' => $quiz->type,
                'duration_minutes' => $quiz->duration_minutes,
                'teacher_name' => $quiz->teacher?->name,
            ],
            'chapter' => ['id' => $quiz->chapter->id, 'label' => $quiz->chapter->label()],
            'subject' => $this->subjectCard($quiz->chapter->subject),
            'question_count' => $quiz->questions()->count(),
            'max_score' => $quiz->maxScore(),
            'file_url' => $quiz->isFile() ? route('muat-turun.kuiz', $quiz) : null,
            'has_ranked_attempt' => $user->isStudent() && $quiz->rankedAttemptFor($user) !== null,
            'my_attempts' => $myAttempts->map(fn ($a) => [
                'id' => $a->id,
                'score' => $a->score,
                'max_score' => $a->max_score,
                'percent' => $a->percentage(),
                'counts_for_ranking' => (bool) $a->counts_for_ranking,
                'completed_at' => $a->completed_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    public function start(Request $request, Quiz $quiz): JsonResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return response()->json(['message' => 'Hanya murid boleh memulakan kuiz.'], 403);
        }

        if (! $quiz->is_published || ! $quiz->isInteractive()) {
            return response()->json(['message' => 'Kuiz tidak tersedia.'], 404);
        }

        if ($quiz->questions()->count() === 0) {
            return response()->json(['message' => 'Kuiz ini belum ada soalan.'], 422);
        }

        // Resume rather than duplicate if the student left one open.
        $open = $quiz->attempts()
            ->where('student_id', $user->id)
            ->whereNull('completed_at')
            ->latest('id')
            ->first();

        $attempt = $open ?? $this->grader->start($quiz, $user);

        $quiz->load('questions.options');

        return response()->json([
            'attempt_id' => $attempt->id,
            'seconds_left' => $this->secondsLeft($attempt),
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'duration_minutes' => $quiz->duration_minutes,
            ],
            'questions' => $quiz->questions->map(fn ($q) => [
                'id' => $q->id,
                'text' => $q->question_text,
                'type' => $q->question_type,
                'points' => $q->points,
                'options' => $q->options->map(fn ($o) => [
                    'id' => $o->id,
                    'letter' => $o->letter(),
                    'text' => $o->option_text,
                ])->all(),
            ])->all(),
        ]);
    }

    public function submit(Request $request, QuizAttempt $attempt): JsonResponse
    {
        $this->authorizeOwnAttempt($request, $attempt);

        if ($attempt->isCompleted()) {
            return $this->resultPayload($attempt->load(['quiz.questions.options', 'answers']));
        }

        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'array'],
            'answers.*.*' => ['integer'],
        ]);

        $graded = $this->grader->grade($attempt, $validated['answers'] ?? []);

        return $this->resultPayload($graded->load(['quiz.questions.options', 'answers']));
    }

    public function result(Request $request, QuizAttempt $attempt): JsonResponse
    {
        $this->authorizeOwnAttempt($request, $attempt);

        if (! $attempt->isCompleted()) {
            return response()->json(['message' => 'Percubaan belum selesai.'], 409);
        }

        return $this->resultPayload(
            $attempt->load(['quiz.questions.options', 'answers']),
        );
    }

    /**
     * The graded view: score/percent plus every question with the correct options and the
     * student's own choices. Safe to expose is_correct here — the attempt is already graded.
     */
    private function resultPayload(QuizAttempt $attempt): JsonResponse
    {
        $answersByQuestion = $attempt->answers->keyBy('question_id');
        $quiz = $attempt->quiz;

        return response()->json([
            'attempt' => [
                'id' => $attempt->id,
                'score' => $attempt->score,
                'max_score' => $attempt->max_score,
                'percent' => $attempt->percentage(),
                'correct_count' => $attempt->correct_count,
                'question_count' => $attempt->question_count,
                'counts_for_ranking' => (bool) $attempt->counts_for_ranking,
                'is_celebration' => $attempt->isCelebration(),
            ],
            'quiz' => ['id' => $quiz->id, 'title' => $quiz->title],
            'questions' => $quiz->questions->map(function ($q) use ($answersByQuestion) {
                $answer = $answersByQuestion->get($q->id);
                $yourIds = $answer ? array_map('intval', $answer->selected_option_ids ?? []) : [];

                return [
                    'id' => $q->id,
                    'text' => $q->question_text,
                    'type' => $q->question_type,
                    'is_correct' => $answer ? (bool) $answer->is_correct : false,
                    'your_option_ids' => $yourIds,
                    'options' => $q->options->map(fn ($o) => [
                        'id' => $o->id,
                        'letter' => $o->letter(),
                        'text' => $o->option_text,
                        'is_correct' => (bool) $o->is_correct,
                    ])->all(),
                ];
            })->all(),
        ]);
    }

    private function authorizeOwnAttempt(Request $request, QuizAttempt $attempt): void
    {
        abort_unless($attempt->student_id === $request->user()->id, Response::HTTP_FORBIDDEN);
    }

    /**
     * Remaining seconds on a timed quiz, or null when untimed, computed from started_at so
     * a reload does not hand the student a fresh timer.
     */
    private function secondsLeft(QuizAttempt $attempt): ?int
    {
        $minutes = $attempt->quiz->duration_minutes;

        if (! $minutes) {
            return null;
        }

        $deadline = $attempt->started_at->copy()->addMinutes($minutes);

        return max(0, (int) now()->diffInSeconds($deadline, absolute: false));
    }
}
