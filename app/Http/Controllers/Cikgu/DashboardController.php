<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Models\Favourite;
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
        $summary = [
            ['icon' => '👁', 'tint' => '#E4EEF9', 'label' => __('Jumlah tontonan video'), 'value' => number_format($stats['views'])],
            ['icon' => '❤️', 'tint' => '#FBE4ED', 'label' => __('Video digemari'), 'value' => number_format($stats['favourites'])],
            ['icon' => '⬇️', 'tint' => '#DCF2EE', 'label' => __('Bahan dimuat turun'), 'value' => number_format($stats['downloads'])],
            ['icon' => '📝', 'tint' => '#FEF0CE', 'label' => __('Percubaan kuiz'), 'value' => number_format($stats['attempts'])],
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
            ['icon' => '🎬', 'title' => __('Video Paling Ditonton'), 'sub' => __('Tontonan pada video anda'),
                'items' => $topVideos->map(fn ($l) => $mapItem($l, $l->views_count))],
            ['icon' => '❤️', 'title' => __('Video Paling Digemari'), 'sub' => __('Murid menandakan ♥ pada video anda'),
                'items' => $topFavourites->map(fn ($l) => $mapItem($l, $l->fav_total))],
            ['icon' => '📄', 'title' => __('Bahan Paling Dimuat Turun'), 'sub' => __('Muat turun pada bahan anda'),
                'items' => $topMaterials->map(fn ($m) => $mapItem($m, $m->download_count))],
            ['icon' => '📝', 'title' => __('Kuiz Paling Dicuba'), 'sub' => __('Percubaan murid pada kuiz anda'),
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
}
