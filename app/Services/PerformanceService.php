<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * "Fokus Saya" - a student's quiz performance broken down by subject and, within each subject,
 * by topic (Bab / chapter). It answers "where should I practise more?" without ever calling a
 * child a failure: subjects are ranked weakest-first so the areas that need work surface at the
 * top. Every subject that has a quiz in the child's Tahun is listed - ones they have tried carry
 * a score, ones still untried are shown greyed so they can see what is left to attempt. (Subjects
 * with no quiz at all are left out; there is nothing to measure or attempt there.)
 *
 * A subject/topic percentage is the mean of the student's BEST attempt on each scoreable quiz
 * they have tried there. Best (not first) is deliberate: it reflects what the child has managed
 * to master after practice, which is the fairest read of current strength.
 *
 * Only interactive quizzes count - printed (file) quizzes are never auto-scored, so they cannot
 * contribute a percentage. Everything is scoped to the student's active Tahun.
 */
class PerformanceService
{
    /** Score at/above which a topic is celebrated. Mirrors the "done" rows on Kuiz Saya. */
    public const BAND_HIGH = 80;

    /** Below this needs the most practice. */
    public const BAND_MID = 50;

    /**
     * @return array{subjects: array<int, array<string, mixed>>}
     */
    public function forStudent(User $student, ?Grade $grade): array
    {
        if (! $grade) {
            return ['subjects' => []];
        }

        // Every scoreable quiz on offer in the student's Tahun, with its topic + subject.
        $quizzes = Quiz::published()
            ->where('type', Quiz::TYPE_INTERACTIVE)
            ->whereHas('chapter', fn ($q) => $q->where('grade_id', $grade->id)->where('is_active', true))
            ->with('chapter.subject', 'chapter.grade')
            ->get()
            ->keyBy('id');

        if ($quizzes->isEmpty()) {
            return ['subjects' => []];
        }

        // Best percentage the student has scored on each quiz they have finished. Attempts on a
        // quiz whose questions were all deleted (max_score 0) are ignored - they are not a real
        // score and percentage() would read 0, dragging the average down for no reason.
        $bestByQuiz = QuizAttempt::completed()
            ->where('student_id', $student->id)
            ->whereIn('quiz_id', $quizzes->keys())
            ->where('max_score', '>', 0)
            ->get()
            ->groupBy('quiz_id')
            ->map(fn (Collection $attempts) => $attempts->max(fn (QuizAttempt $a) => $a->percentage()));

        // Every subject that has a quiz on offer in the student's Tahun. Subjects with no quiz at
        // all are left out - there is nothing to attempt or measure there, and listing the whole
        // curriculum would bury the real signal under rows the child can do nothing with.
        $bySubject = $quizzes->groupBy(fn (Quiz $q) => $q->chapter->subject_id);

        $subjects = [];

        foreach ($bySubject as $subjectQuizzes) {
            $subject = $subjectQuizzes->first()->chapter->subject;

            $attemptedScores = collect();
            $chapters = [];

            foreach ($subjectQuizzes->groupBy(fn (Quiz $q) => $q->chapter_id) as $chapterQuizzes) {
                $chapter = $chapterQuizzes->first()->chapter;

                $scores = $chapterQuizzes
                    ->map(fn (Quiz $q) => $bestByQuiz->get($q->id))
                    ->filter(fn ($p) => $p !== null)
                    ->values();

                $tried = $scores->isNotEmpty();
                $pct = $tried ? (int) round($scores->avg()) : null;

                if ($tried) {
                    $attemptedScores = $attemptedScores->concat($scores);
                }

                // Every topic (Bab) with a quiz is listed - tried topics carry a score, untried
                // ones are shown greyed so the child can see what is left to attempt.
                $chapters[] = [
                    'chapter' => $chapter,
                    'number' => $chapter->number,
                    'attempted' => $tried,
                    'percent' => $pct,
                    'band' => $tried ? $this->band($pct) : null,
                    'attemptedCount' => $scores->count(),
                    'total' => $chapterQuizzes->count(),
                ];
            }

            // Within a subject: tried topics first (weakest first), then untried ones in Bab order.
            usort($chapters, function ($a, $b) {
                if ($a['attempted'] !== $b['attempted']) {
                    return $a['attempted'] ? -1 : 1;
                }

                return $a['attempted']
                    ? $a['percent'] <=> $b['percent']
                    : $a['number'] <=> $b['number'];
            });

            $tried = $attemptedScores->isNotEmpty();
            $pct = $tried ? (int) round($attemptedScores->avg()) : null;

            $subjects[] = [
                'subject' => $subject,
                'attempted' => $tried,
                'hasQuiz' => $subjectQuizzes->isNotEmpty(),
                'percent' => $pct,
                'band' => $tried ? $this->band($pct) : null,
                'quizzesAttempted' => $attemptedScores->count(),
                'quizzesTotal' => $subjectQuizzes->count(),
                'chapters' => $chapters,
            ];
        }

        // Tried subjects first (weakest first, so focus areas surface), then the ones the child
        // has a quiz waiting for but has not tried yet, ordered by name.
        usort($subjects, function ($a, $b) {
            if ($a['attempted'] !== $b['attempted']) {
                return $a['attempted'] ? -1 : 1;
            }

            return $a['attempted']
                ? $a['percent'] <=> $b['percent']
                : strcmp($a['subject']->displayName(), $b['subject']->displayName());
        });

        return ['subjects' => $subjects];
    }

    /** Strength band for a percentage: high / mid / low. Colour + wording live in the view. */
    public function band(int $percent): string
    {
        return match (true) {
            $percent >= self::BAND_HIGH => 'high',
            $percent >= self::BAND_MID => 'mid',
            default => 'low',
        };
    }
}
