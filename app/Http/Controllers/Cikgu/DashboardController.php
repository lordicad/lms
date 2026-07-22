<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Models\Favourite;
use App\Models\LessonView;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $teacher = $request->user();

        $quizIds = $teacher->quizzes()->pluck('id');
        $lessonIds = $teacher->lessons()->pluck('id');

        // Engagement summary (same figures as the Talent page): views, favourites, downloads, attempts.
        $stats = [
            'views' => (int) $teacher->lessons()->sum('views_count'),
            'favourites' => Favourite::whereIn('lesson_id', $lessonIds)->count(),
            'downloads' => (int) $teacher->materials()->sum('download_count'),
            'attempts' => QuizAttempt::whereIn('quiz_id', $quizIds)->completed()->count(),
        ];

        // Newest videos, for the "Video Terbaru Saya" panel.
        $recentLessons = $teacher->lessons()
            ->with('chapter.subject', 'chapter.grade')
            ->latest()
            ->take(4)
            ->get();

        // Newest quizzes with how many distinct students have taken each, for the "Kuiz Saya" panel.
        $recentQuizzes = $teacher->quizzes()
            ->with('chapter.subject', 'chapter.grade')
            ->withCount(['completedAttempts as taken_students_count' => fn ($q) => $q->select(DB::raw('count(distinct student_id)'))])
            ->latest()
            ->take(3)
            ->get();

        // The four engagement summary cards (built here to avoid a Blade @php block).
        // Week on week, from the tables that timestamp each row. Materials are the exception:
        // they carry an aggregate download_count and nothing dated, so that card shows the total
        // with no trend rather than a made-up one.
        $since = now()->subDays(7);

        $summary = [
            [
                'icon' => 'eye', 'tint' => '#E4EEF9', 'ink' => '#2E6CA8',
                'label' => __('Jumlah tontonan video'), 'value' => number_format($stats['views']),
                'trend' => $this->weekTrend(
                    LessonView::whereIn('lesson_id', $lessonIds),
                    'created_at',
                    $since,
                    $stats['views'],
                ),
            ],
            [
                'icon' => 'heart', 'tint' => '#FBE4ED', 'ink' => '#B84A75',
                'label' => __('Video digemari'), 'value' => number_format($stats['favourites']),
                'trend' => $this->weekTrend(
                    Favourite::whereIn('lesson_id', $lessonIds),
                    'created_at',
                    $since,
                    $stats['favourites'],
                ),
            ],
            [
                'icon' => 'download', 'tint' => '#DCF2EE', 'ink' => '#0F7A68',
                'label' => __('Bahan dimuat turun'), 'value' => number_format($stats['downloads']),
                'trend' => null,   // nothing records when a download happened
            ],
            [
                'icon' => 'quiz', 'tint' => '#FEF0CE', 'ink' => '#8A6A12',
                'label' => __('Percubaan kuiz'), 'value' => number_format($stats['attempts']),
                'trend' => $this->weekTrend(
                    QuizAttempt::whereIn('quiz_id', $quizIds)->completed(),
                    'completed_at',
                    $since,
                    $stats['attempts'],
                ),
            ],
        ];

        // Pass/fail across all completed attempts on this teacher's quizzes (shared pass rule).
        $passedAttempts = QuizAttempt::whereIn('quiz_id', $quizIds)->completed()->passed()->count();
        $passFail = [
            'passed' => $passedAttempts,
            'failed' => max(0, $stats['attempts'] - $passedAttempts),
            'total' => $stats['attempts'],
        ];
        $passFailConfig = [
            'type' => 'pie',
            'data' => [
                'labels' => [__('Lulus'), __('Gagal')],
                'datasets' => [[
                    'data' => [$passFail['passed'], $passFail['failed']],
                    'backgroundColor' => ['#0F7A68', '#C24936'],
                    'borderWidth' => 0,
                ]],
            ],
            'options' => ['plugins' => ['legend' => ['position' => 'bottom']]],
        ];

        // Leaderboards over the teacher's own content (same as the Talent page).
        $topVideos = $teacher->lessons()->with('chapter.subject')->orderByDesc('views_count')->take(5)->get();
        $topFavourites = $teacher->lessons()->with('chapter.subject')->withCount(['favourites as fav_total'])
            ->orderByDesc('fav_total')->take(5)->get();
        $topMaterials = $teacher->materials()->with('chapter.subject')->orderByDesc('download_count')->take(5)->get();
        $topQuizzes = $teacher->quizzes()->with('chapter.subject')->withCount('completedAttempts')
            ->orderByDesc('completed_attempts_count')->take(5)->get();

        $mapItem = fn ($model, $value) => [
            'subject' => $model->chapter->subject,
            'title' => $model->title,
            'detail' => $model->chapter->subject->name.' · Bab '.$model->chapter->number,
            'value' => $value,
        ];

        $lists = [
            ['title' => __('Video Paling Ditonton'), 'sub' => __('Tontonan pada video anda'),
                'empty' => __('Tontonan pada video anda akan dipaparkan di sini.'),
                'items' => $topVideos->map(fn ($l) => $mapItem($l, $l->views_count))],
            ['title' => __('Video Paling Digemari'), 'sub' => __('Murid menandakan ♥ pada video anda'),
                'empty' => __('Video yang digemari murid akan dipaparkan di sini.'),
                'items' => $topFavourites->map(fn ($l) => $mapItem($l, $l->fav_total))],
            ['title' => __('Bahan Paling Dimuat Turun'), 'sub' => __('Muat turun pada bahan anda'),
                'empty' => __('Bahan yang dimuat turun murid akan dipaparkan di sini.'),
                'items' => $topMaterials->map(fn ($m) => $mapItem($m, $m->download_count))],
            ['title' => __('Kuiz Paling Dicuba'), 'sub' => __('Percubaan murid pada kuiz anda'),
                'empty' => __('Percubaan murid pada kuiz anda akan dipaparkan di sini.'),
                'items' => $topQuizzes->map(fn ($q) => $mapItem($q, $q->completed_attempts_count))],
        ];

        return view('cikgu.dashboard', [
            'stats' => $stats,
            'summary' => $summary,
            'passFail' => $passFail,
            'passFailConfig' => $passFailConfig,
            'lists' => $lists,
            'totalStudents' => User::where('role', User::ROLE_STUDENT)->count(),
            'recentLessons' => $recentLessons,
            'recentQuizzes' => $recentQuizzes,
        ]);
    }

    /**
     * How much of the running total arrived in the last seven days.
     *
     * Expressed against the total rather than against the previous week: a teacher with 3 views
     * last week and 6 this week is better served by "6 of your 9 views came this week" than by
     * "+100%", which reads as a milestone on numbers that small.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     * @return array{count: int, percent: int}|null
     */
    private function weekTrend($query, string $column, \Carbon\CarbonInterface $since, int $total): ?array
    {
        if ($total < 1) {
            return null;
        }

        $recent = (clone $query)->where($column, '>=', $since)->count();

        if ($recent < 1) {
            return null;
        }

        return ['count' => $recent, 'percent' => (int) round($recent / $total * 100)];
    }
}
