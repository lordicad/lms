<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Favourite;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Teacher home for the mobile app: the same engagement summary and quiz outcome
 * signal surfaced by the web Cikgu dashboard.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $teacher = $request->user();

        if (! $teacher->isTeacher()) {
            return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
        }

        $quizIds = $teacher->quizzes()->pluck('id');
        $lessonIds = $teacher->lessons()->pluck('id');
        $completedAttempts = QuizAttempt::whereIn('quiz_id', $quizIds)->completed();
        $attempts = (clone $completedAttempts)->count();
        $passed = (clone $completedAttempts)->passed()->count();

        $recent = QuizAttempt::whereIn('quiz_id', $quizIds)
            ->completed()
            ->with('student.grade', 'quiz.chapter.subject')
            ->orderByDesc('completed_at')
            ->take(8)
            ->get();

        return response()->json([
            'stats' => [
                'views' => (int) $teacher->lessons()->sum('views_count'),
                'favourites' => Favourite::whereIn('lesson_id', $lessonIds)->count(),
                'downloads' => (int) $teacher->materials()->sum('download_count'),
                'attempts' => $attempts,
            ],
            'pass_fail' => [
                'passed' => $passed,
                'failed' => max(0, $attempts - $passed),
                'total' => $attempts,
            ],
            'recent_attempts' => $recent->map(fn ($a) => [
                'student_name' => $a->student?->name,
                'grade_name' => $a->student?->grade?->name,
                'quiz_title' => $a->quiz?->title,
                'subject_name' => $a->quiz?->chapter?->subject?->displayName(),
                'percent' => $a->percentage(),
                'completed_at' => $a->completed_at?->toIso8601String(),
            ])->all(),
        ]);
    }
}
