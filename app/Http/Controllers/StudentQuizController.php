<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\LeaderboardService;
use App\Support\ActiveGrade;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentQuizController extends Controller
{
    /**
     * Every published quiz in the student's Tahun - attempted and not - with their ranked score
     * where they have finished one.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $grade = ActiveGrade::for($user);

        $quizzes = $grade
            ? Quiz::published()
                ->whereHas('chapter', fn ($q) => $q->where('grade_id', $grade->id)->where('is_active', true))
                ->with('chapter.subject', 'chapter.grade')
                ->withCount('questions')
                ->orderByDesc('id')
                ->get()
            : collect();

        // The ranked (first completed) attempt per quiz, so each row shows a score or "not tried".
        $rankedAttempts = QuizAttempt::where('student_id', $user->id)
            ->where('counts_for_ranking', true)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->get()
            ->keyBy('quiz_id');

        // Headline stats for the WeLearn stats strip.
        $doneCount = $rankedAttempts->count();
        // Average only real scores: a quiz whose questions were all deleted leaves 0/0 attempts that
        // read as 0% and would drag this down (PerformanceService skips them for the same reason).
        $scored = $rankedAttempts->filter(fn ($a) => $a->max_score > 0);
        $avgScore = $scored->isNotEmpty() ? (int) round($scored->avg->percentage()) : null;
        $myRow = app(LeaderboardService::class)->rowFor($user);

        return view('belajar.kuiz-saya', [
            'grade' => $grade,
            'quizzes' => $quizzes,
            'rankedAttempts' => $rankedAttempts,
            'doneCount' => $doneCount,
            'avgScore' => $avgScore,
            'rank' => $myRow?->rank,
            // Perfect-score tally that drives the milestone badges below the stats strip.
            'perfectCount' => app(\App\Services\BadgeService::class)->perfectQuizCount($user),
            // "Fokus Saya": per-subject / per-topic strength, weakest first, from best attempts.
            'performance' => app(\App\Services\PerformanceService::class)->forStudent($user, $grade),
        ]);
    }
}
