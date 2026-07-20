<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\TalentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TalentService $talentService): View
    {
        $teacher = $request->user();

        $quizIds = $teacher->quizzes()->pluck('id');

        return view('cikgu.dashboard', [
            // Scalar totals (kept from the old Home, reorganised).
            'lessonCount' => $teacher->lessons()->count(),
            'materialCount' => $teacher->materials()->count(),
            'quizCount' => $teacher->quizzes()->count(),
            'viewCount' => (int) $teacher->lessons()->sum('views_count'),

            // A. Interactive content-performance metrics (top 5 + Others per metric).
            'contentMetrics' => $this->contentMetrics($teacher),

            // B. Weekly upload activity (last 7 calendar days incl. today, per content type).
            'weeklyTrend' => $this->weeklyTrend($teacher),

            // C. Student quiz pass/fail across all of this teacher's quizzes.
            'passFail' => $this->passFail($quizIds),

            // D. Keep the transparent Talent signal on Home (TalentService is not discarded).
            'talent' => $talentService->forTeacher($teacher),

            // Recent content lists (kept).
            'recentLessons' => $teacher->lessons()
                ->with('chapter.subject', 'chapter.grade')->latest()->take(4)->get(),
        ]);
    }

    /**
     * The four selectable content-performance metrics, each as top 5 items + an "Others" bucket,
     * with a raw count and a link to the matching teacher-owned destination.
     *
     * @return array<string, array{label: string, items: array<int, array<string, mixed>>, total: int}>
     */
    private function contentMetrics(User $teacher): array
    {
        $views = $teacher->lessons()->orderByDesc('views_count')
            ->get(['id', 'title', 'views_count']);

        $favourites = $teacher->lessons()->withCount(['favourites as fav_total'])
            ->orderByDesc('fav_total')->get(['id', 'title']);

        $downloads = $teacher->materials()->orderByDesc('download_count')
            ->get(['id', 'title', 'download_count', 'chapter_id']);

        $attempts = $teacher->quizzes()->withCount(['completedAttempts as attempts_total'])
            ->orderByDesc('attempts_total')->get(['id', 'title', 'type', 'chapter_id']);

        return [
            'views' => [
                'label' => __('Video paling ditonton'),
                ...$this->topWithOthers($views, 'views_count', fn ($l) => route('video.show', $l->id)),
            ],
            'favourites' => [
                'label' => __('Video paling digemari'),
                ...$this->topWithOthers($favourites, 'fav_total', fn ($l) => route('video.show', $l->id)),
            ],
            'downloads' => [
                'label' => __('Bahan paling dimuat turun'),
                ...$this->topWithOthers($downloads, 'download_count', fn ($m) => route('cikgu.bab.show', $m->chapter_id)),
            ],
            'attempts' => [
                'label' => __('Kuiz paling dicuba'),
                ...$this->topWithOthers($attempts, 'attempts_total', fn ($q) => $q->isInteractive()
                    ? route('cikgu.kuiz.statistik', $q->id)
                    : route('cikgu.bab.show', $q->chapter_id)),
            ],
        ];
    }

    /**
     * Take a descending collection, keep the top 5 with a link, fold the remaining non-zero rows
     * into "Lain-lain", and return the displayed total (sum of all non-zero values).
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    private function topWithOthers(Collection $rows, string $valueKey, callable $url): array
    {
        $nonZero = $rows->filter(fn ($row) => (int) $row->{$valueKey} > 0)->values();

        $items = $nonZero->take(5)->map(fn ($row) => [
            'label' => $row->title,
            'value' => (int) $row->{$valueKey},
            'url' => $url($row),
        ])->values()->all();

        $othersSum = (int) $nonZero->slice(5)->sum($valueKey);
        if ($othersSum > 0) {
            $items[] = ['label' => __('Lain-lain'), 'value' => $othersSum, 'url' => null];
        }

        return ['items' => $items, 'total' => (int) $nonZero->sum($valueKey)];
    }

    /**
     * Videos + Materials + Quizzes created per day over the last 7 calendar days, in the app
     * timezone, with zero-activity days kept so the axis is continuous.
     *
     * @return array{labels: array<int, string>, videos: array<int, int>, materials: array<int, int>, quizzes: array<int, int>}
     */
    private function weeklyTrend(User $teacher): array
    {
        $start = Carbon::now()->startOfDay()->subDays(6);

        $days = collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i));
        $keys = $days->map(fn ($day) => $day->toDateString());

        $bucket = function (Collection $timestamps) use ($keys): array {
            $grouped = $timestamps->groupBy(fn ($ts) => $ts->toDateString())->map->count();

            return $keys->map(fn ($key) => (int) ($grouped[$key] ?? 0))->all();
        };

        return [
            'labels' => $days->map(fn ($day) => $day->translatedFormat('D, d/m'))->all(),
            'videos' => $bucket($teacher->lessons()->where('created_at', '>=', $start)->pluck('created_at')),
            'materials' => $bucket($teacher->materials()->where('created_at', '>=', $start)->pluck('created_at')),
            'quizzes' => $bucket($teacher->quizzes()->where('created_at', '>=', $start)->pluck('created_at')),
        ];
    }

    /**
     * Passed vs failed across every completed attempt on this teacher's quizzes, using the app's
     * shared pass rule so it agrees with the result pages and reports.
     *
     * @param  Collection<int, int>  $quizIds
     * @return array{passed: int, failed: int, total: int}
     */
    private function passFail(Collection $quizIds): array
    {
        if ($quizIds->isEmpty()) {
            return ['passed' => 0, 'failed' => 0, 'total' => 0];
        }

        $total = QuizAttempt::whereIn('quiz_id', $quizIds)->completed()->count();
        $passed = QuizAttempt::whereIn('quiz_id', $quizIds)->completed()->passed()->count();

        return ['passed' => $passed, 'failed' => max(0, $total - $passed), 'total' => $total];
    }
}
