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

        return view('cikgu.dashboard', [
            'stats' => $stats,
            'summary' => $summary,
            'totalStudents' => User::where('role', User::ROLE_STUDENT)->count(),
            'recentLessons' => $recentLessons,
            'recentQuizzes' => $recentQuizzes,
        ]);
    }
}
