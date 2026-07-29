<?php

namespace App\Services;

use App\Models\QuizAttempt;
use App\Models\User;
use App\Support\QuizBadges;
use Illuminate\Support\Collection;

/**
 * Quiz badges are derived, never stored: everything here is computed from quiz_attempts on read.
 * That keeps the badge a student sees always consistent with their attempts, and means the day
 * this shipped every past attempt already counts — no backfill.
 *
 * Rules (see QuizBadges for thresholds):
 *   completed / tier   — earned from the FIRST (ranked) attempt only.
 *   never_give_up      — three or more attempts on the same quiz.
 *   comeback           — the first attempt failed and a later one passed.
 */
class BadgeService
{
    /**
     * Badges owned for one quiz as of $attempt, and which were newly earned on it.
     *
     * @return array{earned: list<string>, new: list<string>}
     */
    public function forAttempt(QuizAttempt $attempt): array
    {
        $attempts = $this->completedAttempts($attempt->student_id, $attempt->quiz_id);

        // State as of this attempt (results pages are usually the latest, but an old one still
        // shows what was true then) and the state just before it, so the delta is what's new.
        $upTo = $attempts->filter(fn (QuizAttempt $a) => $this->notAfter($a, $attempt))->values();
        $before = $upTo->reject(fn (QuizAttempt $a) => $a->id === $attempt->id)->values();

        $earned = $this->earnedFrom($upTo);

        return [
            'earned' => $earned,
            'new' => array_values(array_diff($earned, $this->earnedFrom($before))),
        ];
    }

    /**
     * How many of each badge the student holds across every quiz they've attempted, for the
     * profile collection. Each badge is counted at most once per quiz.
     *
     * @return array<string, int>
     */
    public function collectionFor(User $student): array
    {
        $byQuiz = QuizAttempt::query()
            ->where('student_id', $student->id)
            ->whereNotNull('completed_at')
            ->orderBy('completed_at')
            ->orderBy('id')
            ->get()
            ->groupBy('quiz_id');

        $counts = array_fill_keys(QuizBadges::order(), 0);

        foreach ($byQuiz as $attempts) {
            foreach ($this->earnedFrom($attempts) as $key) {
                $counts[$key]++;
            }
        }

        return $counts;
    }

    /**
     * How many quizzes the student has a perfect (100%) first-attempt score on. Drives the
     * milestone badges on the Quizzes page. One ranked attempt per quiz, so this is a distinct
     * quiz count.
     */
    public function perfectQuizCount(User $student): int
    {
        return QuizAttempt::query()
            ->where('student_id', $student->id)
            ->where('counts_for_ranking', true)
            ->where('max_score', '>', 0)
            ->whereColumn('score', 'max_score')
            ->count();
    }

    /**
     * @return Collection<int, QuizAttempt>
     */
    private function completedAttempts(int $studentId, int $quizId): Collection
    {
        return QuizAttempt::query()
            ->where('student_id', $studentId)
            ->where('quiz_id', $quizId)
            ->whereNotNull('completed_at')
            ->orderBy('completed_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * The set of badge keys earned from a student's attempts on ONE quiz, oldest first.
     *
     * @param  Collection<int, QuizAttempt>  $attempts
     * @return list<string>
     */
    private function earnedFrom(Collection $attempts): array
    {
        if ($attempts->isEmpty()) {
            return [];
        }

        $earned = ['completed'];

        // Tier is fixed by the first attempt — the one that feeds the leaderboard.
        $first = $attempts->firstWhere('counts_for_ranking', true) ?? $attempts->first();
        if ($tier = QuizBadges::tierFor((int) $first->score, (int) $first->max_score)) {
            $earned[] = $tier;
        }

        if ($attempts->count() >= 3) {
            $earned[] = 'never_give_up';
        }

        // Redemption: first attempt failed, a later attempt cleared the pass mark.
        $firstFailed = ! QuizBadges::passed((int) $first->score, (int) $first->max_score);
        $laterPass = $attempts->slice(1)->contains(
            fn (QuizAttempt $a) => QuizBadges::passed((int) $a->score, (int) $a->max_score)
        );
        if ($firstFailed && $laterPass) {
            $earned[] = 'comeback';
        }

        return array_values(array_intersect(QuizBadges::order(), $earned));
    }

    private function notAfter(QuizAttempt $a, QuizAttempt $ref): bool
    {
        if ($a->completed_at && $ref->completed_at && $a->completed_at->ne($ref->completed_at)) {
            return $a->completed_at->lt($ref->completed_at);
        }

        return $a->id <= $ref->id;
    }
}
