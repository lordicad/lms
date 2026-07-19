<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Services\TalentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A teacher's own talent signal, presented as an engagement dashboard: four headline totals
 * (views, favourites, downloads, quiz attempts) and four leaderboards of their own content.
 * Read-only. (YouTube channel verification lives on the profile page.)
 */
class TalentController extends Controller
{
    public function __invoke(Request $request, TalentService $talent): View
    {
        $teacher = $request->user();
        $result = $talent->forTeacher($teacher);

        // Leaderboards over the teacher's own content.
        $topVideos = $teacher->lessons()->with('chapter.subject')
            ->orderByDesc('views_count')->take(5)->get();

        $topMaterials = $teacher->materials()->with('chapter.subject')
            ->orderByDesc('download_count')->take(5)->get();

        $topQuizzes = $teacher->quizzes()->with('chapter.subject')
            ->withCount('completedAttempts')
            ->orderByDesc('completed_attempts_count')->take(5)->get();

        // Favourites are already computed per lesson by the talent service.
        $topFavourites = $result->lessons->sortByDesc('favourites')->take(5)->values();

        return view('cikgu.bakat', [
            'result' => $result,
            'topVideos' => $topVideos,
            'topMaterials' => $topMaterials,
            'topQuizzes' => $topQuizzes,
            'topFavourites' => $topFavourites,
            'stats' => [
                'views' => (int) $teacher->lessons()->sum('views_count'),
                'favourites' => (int) $result->lessons->sum('favourites'),
                'downloads' => (int) $teacher->materials()->sum('download_count'),
                'attempts' => QuizAttempt::whereIn('quiz_id', $teacher->quizzes()->pluck('id'))->completed()->count(),
            ],
        ]);
    }
}
