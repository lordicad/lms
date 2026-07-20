<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\Subject;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Teacher-facing student leaderboard for Flutter. It deliberately reads the same
 * LeaderboardService as the web Cikgu ranking page and student app, so every
 * surface applies the identical first-attempt and tie-break rules.
 */
class RankingController extends Controller
{
    public function __invoke(Request $request, LeaderboardService $leaderboard): JsonResponse
    {
        if (! $request->user()?->isTeacher()) {
            return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
        }

        $filters = $request->validate([
            'grade_id' => ['nullable', 'integer', Rule::exists('grades', 'id')],
            'subject_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')],
            'quiz_id' => ['nullable', 'integer', Rule::exists('quizzes', 'id')],
        ]);

        $gradeId = $filters['grade_id'] ?? null;
        $subjectId = $filters['subject_id'] ?? null;
        $quizId = $filters['quiz_id'] ?? null;

        $rows = $leaderboard->ranking(
            gradeId: $gradeId,
            subjectId: $subjectId,
            quizId: $quizId,
        );

        // Keep the quiz dropdown aligned with the two broader filters, exactly
        // like the web Cikgu ranking page.
        $quizzes = Quiz::query()
            ->where('type', Quiz::TYPE_INTERACTIVE)
            ->when($gradeId, fn ($query) => $query->whereHas(
                'chapter',
                fn ($chapter) => $chapter->where('grade_id', $gradeId),
            ))
            ->when($subjectId, fn ($query) => $query->whereHas(
                'chapter',
                fn ($chapter) => $chapter->where('subject_id', $subjectId),
            ))
            ->orderBy('title')
            ->get();

        return response()->json([
            'filters' => [
                'grades' => Grade::orderBy('level')->get()->map(fn (Grade $grade) => [
                    'id' => $grade->id,
                    'name' => $grade->name,
                ])->all(),
                'subjects' => Subject::orderBy('sort_order')->get()->map(fn (Subject $subject) => [
                    'id' => $subject->id,
                    'name' => $subject->displayName(),
                ])->all(),
                'quizzes' => $quizzes->map(fn (Quiz $quiz) => [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                ])->all(),
                'selected' => [
                    'grade_id' => $gradeId,
                    'subject_id' => $subjectId,
                    'quiz_id' => $quizId,
                ],
            ],
            'rows' => $rows->map(fn (object $row) => [
                'rank' => $row->rank,
                'student_name' => $row->student->name,
                'initials' => $row->student->initials(),
                'grade_name' => $row->student->grade?->name,
                'points' => $row->points,
                'correct' => $row->correct,
                'questions' => $row->questions,
                'accuracy' => $row->accuracy,
                'quizzes' => $row->quizzes,
            ])->all(),
        ]);
    }
}
