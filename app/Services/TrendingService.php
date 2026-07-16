<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * "Apa yang murid suka" — a lightweight popularity score per lesson, scoped to one Tahun.
 *
 *   score = views_count * 1
 *         + favourites_count * 3
 *         + (distinct students who watched in the last 14 days) * 2
 *         + (completions in the last 14 days) * 2
 *
 * Cached per grade for 15 minutes (file cache is fine — no Redis on cPanel). Because production
 * starts nearly empty, when fewer than 5 lessons have any signal at all it falls back to
 * newest-first so the rail is never empty or one item long; the caller relabels it accordingly.
 *
 * Test accounts (username `autopilot.*`) are excluded from the freshness signals.
 */
class TrendingService
{
    public const WINDOW_DAYS = 14;

    public const CACHE_MINUTES = 15;

    /** Below this many lessons with any signal, fall back to newest-first. */
    public const MIN_SIGNALS = 5;

    /**
     * @return array{lessons: Collection<int, Lesson>, fallback: bool}
     */
    public function forGrade(int $gradeId, int $limit = 12): array
    {
        $resolved = Cache::remember(
            "trending:grade:{$gradeId}",
            self::CACHE_MINUTES * 60,
            fn () => $this->rank($gradeId, $limit),
        );

        // Hydrate fresh models in the ranked order (ids are cheap and safe to cache; models are not).
        $lessons = Lesson::published()
            ->whereIn('id', $resolved['ids'])
            ->with('chapter.subject')
            ->get()
            ->sortBy(fn (Lesson $lesson) => array_search($lesson->id, $resolved['ids'], true))
            ->values();

        return ['lessons' => $lessons, 'fallback' => $resolved['fallback']];
    }

    /**
     * @return array{ids: array<int, int>, fallback: bool}
     */
    private function rank(int $gradeId, int $limit): array
    {
        $window = now()->subDays(self::WINDOW_DAYS);

        $notAutopilot = fn ($query) => $query
            ->join('users', 'users.id', '=', 'lesson_progress.student_id')
            ->where('users.username', 'not like', 'autopilot.%');

        $lessons = Lesson::query()
            ->published()
            ->whereHas('chapter', fn ($query) => $query->where('grade_id', $gradeId))
            ->select('lessons.*')
            ->selectSub(
                LessonProgress::query()
                    ->tap($notAutopilot)
                    ->whereColumn('lesson_progress.lesson_id', 'lessons.id')
                    ->where('lesson_progress.last_watched_at', '>=', $window)
                    ->selectRaw('count(distinct lesson_progress.student_id)'),
                'recent_watchers',
            )
            ->selectSub(
                LessonProgress::query()
                    ->tap($notAutopilot)
                    ->whereColumn('lesson_progress.lesson_id', 'lessons.id')
                    ->where('lesson_progress.completed', true)
                    ->where('lesson_progress.last_watched_at', '>=', $window)
                    ->selectRaw('count(*)'),
                'recent_completions',
            )
            ->get();

        $scored = $lessons->map(function (Lesson $lesson) {
            $lesson->trend_score = $lesson->views_count
                + $lesson->favourites_count * 3
                + (int) $lesson->recent_watchers * 2
                + (int) $lesson->recent_completions * 2;

            return $lesson;
        });

        $withSignal = $scored->filter(fn (Lesson $lesson) => $lesson->trend_score > 0);

        if ($withSignal->count() < self::MIN_SIGNALS) {
            return [
                'ids' => $scored->sortByDesc('id')->take($limit)->pluck('id')->all(),
                'fallback' => true,
            ];
        }

        return [
            'ids' => $scored->sortByDesc('trend_score')->take($limit)->pluck('id')->all(),
            'fallback' => false,
        ];
    }
}
