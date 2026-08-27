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
 * top, but every subject the student has tried is shown, with an encouraging band label.
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
     * @return array{subjects: array<int, array<string, mixed>>, notAttempted: Collection<int, \App\Models\Subject>}
     */
    public function forStudent(User $student, ?Grade $grade): array
    {
        if (! $grade) {
            return ['subjects' => [], 'notAttempted' => collect()];
        }

        // Every scoreable quiz on offer in the student's Tahun, with its topic + subject.
        $quizzes = Quiz::published()
            ->where('type', Quiz::TYPE_INTERACTIVE)
            ->whereHas('chapter', fn ($q) => $q->where('grade_id', $grade->id)->where('is_active', true))
            ->with('chapter.subject', 'chapter.grade')
            ->get()
            ->keyBy('id');

        if ($quizzes->isEmpty()) {
            return ['subjects' => [], 'notAttempted' => collect()];
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

        // Group the on-offer quizzes by subject, then by chapter, carrying each quiz's best score
        // (or null when untried) so we can show "3 of 5 tried" alongside the average.
        $bySubject = $quizzes->groupBy(fn (Quiz $q) => $q->chapter->subject_id);

        $subjects = [];
        $notAttempted = collect();

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

                if ($scores->isEmpty()) {
                    continue; // topic not tried yet - leave it out of the subject's average
                }

                $attemptedScores = $attemptedScores->concat($scores);
                $pct = (int) round($scores->avg());

                $chapters[] = [
                    'chapter' => $chapter,
                    'percent' => $pct,
                    'band' => $this->band($pct),
                    'attempted' => $scores->count(),
                    'total' => $chapterQuizzes->count(),
                ];
            }

            if ($attemptedScores->isEmpty()) {
                $notAttempted->push($subject);

                continue;
            }

            // Weakest topic first, so the child sees what to practise at a glance.
            usort($chapters, fn ($a, $b) => $a['percent'] <=> $b['percent']);

            $pct = (int) round($attemptedScores->avg());

            $subjects[] = [
                'subject' => $subject,
                'percent' => $pct,
                'band' => $this->band($pct),
                'quizzesAttempted' => $attemptedScores->count(),
                'quizzesTotal' => $subjectQuizzes->count(),
                'chapters' => $chapters,
            ];
        }

        // Weakest subject first.
        usort($subjects, fn ($a, $b) => $a['percent'] <=> $b['percent']);

        return [
            'subjects' => $subjects,
            'notAttempted' => $notAttempted->sortBy(fn ($s) => $s->displayName())->values(),
        ];
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
