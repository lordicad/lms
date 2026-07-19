<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
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

        return view('cikgu.dashboard', [
            'lessonCount' => $teacher->lessons()->count(),
            'materialCount' => $teacher->materials()->count(),
            'quizCount' => $teacher->quizzes()->count(),
            'attemptCount' => QuizAttempt::whereIn('quiz_id', $quizIds)->completed()->count(),
            'viewCount' => (int) $teacher->lessons()->sum('views_count'),
            'totalStudents' => User::where('role', User::ROLE_STUDENT)->count(),
            'recentLessons' => $recentLessons,
            'recentQuizzes' => $recentQuizzes,
        ]);
    }
}
