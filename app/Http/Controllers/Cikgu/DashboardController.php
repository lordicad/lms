<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $teacher = $request->user();

        $quizIds = $teacher->quizzes()->pluck('id');

        $latestAttempts = QuizAttempt::whereIn('quiz_id', $quizIds)
            ->completed()
            ->with('student.grade', 'quiz.chapter.subject')
            ->orderByDesc('completed_at')
            ->take(5)
            ->get();

        return view('cikgu.dashboard', [
            'lessonCount' => $teacher->lessons()->count(),
            'materialCount' => $teacher->materials()->count(),
            'quizCount' => $teacher->quizzes()->count(),
            'attemptCount' => QuizAttempt::whereIn('quiz_id', $quizIds)->completed()->count(),
            'viewCount' => (int) $teacher->lessons()->sum('views_count'),
            'latestAttempts' => $latestAttempts,
        ]);
    }
}
