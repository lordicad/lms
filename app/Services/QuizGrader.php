<?php

namespace App\Services;

use App\Models\AttemptAnswer;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuizGrader
{
    /**
     * Opens an attempt. The very first attempt a student completes on a quiz is the one that
     * feeds the leaderboard; everything after it is practice.
     *
     * The flag is decided here, at start time, by asking whether a ranked attempt already
     * exists. Retrying is unlimited and never changes a student's points.
     */
    public function start(Quiz $quiz, User $student): QuizAttempt
    {
        $alreadyRanked = $quiz->attempts()
            ->where('student_id', $student->id)
            ->where('counts_for_ranking', true)
            ->exists();

        return QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'score' => 0,
            'max_score' => $quiz->maxScore(),
            'correct_count' => 0,
            'question_count' => $quiz->questions()->count(),
            'counts_for_ranking' => ! $alreadyRanked,
            'started_at' => now(),
        ]);
    }

    /**
     * Grades an attempt server-side. The browser only ever sends option ids; which of them
     * are correct is never exposed to the client before submission.
     *
     * Scoring (plan 7.8):
     *   single   - full points when the chosen option is the correct one.
     *   multiple - all-or-nothing: the chosen set must equal the correct set exactly.
     *
     * @param  array<int, array<int, int|string>>  $answers  question_id => [option_id, ...]
     */
    public function grade(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        $quiz = $attempt->quiz()->with('questions.options')->firstOrFail();

        return DB::transaction(function () use ($attempt, $quiz, $answers) {
            $attempt->answers()->delete();

            $score = 0;
            $correctCount = 0;

            foreach ($quiz->questions as $question) {
                $selected = array_values(array_unique(array_map(
                    'intval',
                    (array) ($answers[$question->id] ?? []),
                )));

                // Only options that actually belong to this question may be counted, so a
                // hand-crafted POST cannot smuggle in ids from another quiz.
                $validIds = $question->options->pluck('id')->map(fn ($id) => (int) $id)->all();
                $selected = array_values(array_intersect($selected, $validIds));

                // A radio question can never accept two answers.
                if (! $question->isMultiple() && count($selected) > 1) {
                    $selected = [$selected[0]];
                }

                $isCorrect = $question->isAnswerCorrect($selected);
                $points = $isCorrect ? $question->points : 0;

                AttemptAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_option_ids' => $selected,
                    'is_correct' => $isCorrect,
                    'points_awarded' => $points,
                ]);

                $score += $points;
                $correctCount += $isCorrect ? 1 : 0;
            }

            $completedAt = now();

            $attempt->update([
                'score' => $score,
                'max_score' => (int) $quiz->questions->sum('points'),
                'correct_count' => $correctCount,
                'question_count' => $quiz->questions->count(),
                'completed_at' => $completedAt,
                'duration_seconds' => max(0, $completedAt->diffInSeconds($attempt->started_at, absolute: true)),
            ]);

            return $attempt->fresh();
        });
    }
}
