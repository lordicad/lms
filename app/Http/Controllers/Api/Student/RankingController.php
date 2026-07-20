<?php

namespace App\Http\Controllers\Api\Student;

use App\Models\Subject;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Student leaderboard for the mobile app, scoped to the student's own Tahun. Mirrors the web
 * RankingController and reads from LeaderboardService so mobile and web never drift apart.
 * Optional ?subjek=<slug> filters by subject.
 */
class RankingController extends StudentApiController
{
    public function __invoke(Request $request, LeaderboardService $leaderboard): JsonResponse
    {
        $user = $request->user();
        $grade = $this->resolveGrade($request, $user);

        $subject = $request->filled('subjek')
            ? Subject::where('slug', $request->string('subjek'))->first()
            : null;

        $top = $leaderboard->ranking(
            gradeId: $grade?->id,
            subjectId: $subject?->id,
            limit: 10,
        );

        $myRow = $user->isStudent() ? $leaderboard->rowFor($user, $subject?->id) : null;
        $inTop = $myRow && $top->contains(fn ($row) => $row->student->id === $user->id);

        return response()->json([
            'grade' => $grade ? $this->gradePayload($grade) : null,
            'subject' => $subject ? $this->subjectCard($subject) : null,
            'top' => $top->map(fn ($row) => $this->rankRow($row, $user->id))->all(),
            'my_row' => $myRow ? $this->rankRow($myRow, $user->id) : null,
            // Pin the student's own row when they sit outside the Top 10.
            'show_my_row' => $myRow && ! $inTop,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rankRow(object $row, int $meId): array
    {
        return [
            'rank' => $row->rank,
            'name' => $row->student->name,
            'points' => $row->points,
            'accuracy' => $row->accuracy,
            'quizzes' => $row->quizzes,
            'is_me' => $row->student->id === $meId,
        ];
    }
}
