<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The one place ranking is computed. Both /ranking (students) and /cikgu/ranking (teachers)
 * read from here, so the two views can never drift apart.
 *
 * Points  = sum of `score` over attempts where counts_for_ranking = true (first attempt only).
 * Ties    = higher accuracy first, then the earlier last-completion.
 */
class LeaderboardService
{
    /**
     * @return Collection<int, object{
     *     student: User, points: int, correct: int, questions: int,
     *     accuracy: float, quizzes: int, last_completed_at: string|null, rank: int
     * }>
     */
    public function ranking(
        ?int $gradeId = null,
        ?int $subjectId = null,
        ?int $quizId = null,
        ?int $limit = null,
    ): Collection {
        $rows = DB::table('quiz_attempts')
            ->join('users', 'users.id', '=', 'quiz_attempts.student_id')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->join('chapters', 'chapters.id', '=', 'quizzes.chapter_id')
            ->where('quiz_attempts.counts_for_ranking', true)
            ->whereNotNull('quiz_attempts.completed_at')
            ->where('users.role', User::ROLE_STUDENT)
            ->when($gradeId, fn ($q) => $q->where('users.grade_id', $gradeId))
            ->when($subjectId, fn ($q) => $q->where('chapters.subject_id', $subjectId))
            ->when($quizId, fn ($q) => $q->where('quizzes.id', $quizId))
            ->groupBy('users.id')
            ->select([
                'users.id as student_id',
                DB::raw('SUM(quiz_attempts.score) as points'),
                DB::raw('SUM(quiz_attempts.correct_count) as correct'),
                DB::raw('SUM(quiz_attempts.question_count) as questions'),
                DB::raw('COUNT(quiz_attempts.id) as quizzes'),
                DB::raw('MAX(quiz_attempts.completed_at) as last_completed_at'),
            ])
            ->get();

        $students = User::with('grade')
            ->whereIn('id', $rows->pluck('student_id'))
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function ($row) use ($students) {
                $questions = (int) $row->questions;

                return (object) [
                    'student' => $students[$row->student_id],
                    'points' => (int) $row->points,
                    'correct' => (int) $row->correct,
                    'questions' => $questions,
                    'accuracy' => $questions > 0 ? round((int) $row->correct / $questions * 100, 1) : 0.0,
                    'quizzes' => (int) $row->quizzes,
                    'last_completed_at' => $row->last_completed_at,
                    'rank' => 0,
                ];
            })
            // Tie-breakers, applied in order: points, then accuracy, then who got there first.
            ->sortBy([
                fn ($a, $b) => $b->points <=> $a->points,
                fn ($a, $b) => $b->accuracy <=> $a->accuracy,
                fn ($a, $b) => ($a->last_completed_at ?? '9999') <=> ($b->last_completed_at ?? '9999'),
            ])
            ->values()
            ->each(fn ($row, $index) => $row->rank = $index + 1)
            ->when($limit, fn (Collection $c) => $c->take($limit))
            ->values();
    }

    /**
     * A student's own row, wherever they sit in the table. Used to pin them below the Top 10
     * so they always see their standing even when they are 47th.
     *
     * @return object|null
     */
    public function rowFor(User $student, ?int $subjectId = null): ?object
    {
        return $this->ranking(gradeId: $student->grade_id, subjectId: $subjectId)
            ->firstWhere('student.id', $student->id);
    }

    /**
     * Total ranked points for a student across every subject. Shown on their dashboard.
     */
    public function pointsFor(User $student): int
    {
        return (int) $student->attempts()
            ->where('counts_for_ranking', true)
            ->whereNotNull('completed_at')
            ->sum('score');
    }
}
