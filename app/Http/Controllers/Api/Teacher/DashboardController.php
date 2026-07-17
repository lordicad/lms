<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Teacher home for the mobile app: content counts, total views, and the latest quiz
 * attempts across this teacher's quizzes. Mirrors the web Cikgu\DashboardController.
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

        $recent = QuizAttempt::whereIn('quiz_id', $quizIds)
            ->completed()
            ->with('student.grade', 'quiz.chapter.subject')
            ->orderByDesc('completed_at')
            ->take(8)
            ->get();

        return response()->json([
            'stats' => [
                'videos' => $teacher->lessons()->count(),
                'materials' => $teacher->materials()->count(),
                'quizzes' => $teacher->quizzes()->count(),
                'attempts' => QuizAttempt::whereIn('quiz_id', $quizIds)->completed()->count(),
                'views' => (int) $teacher->lessons()->sum('views_count'),
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
