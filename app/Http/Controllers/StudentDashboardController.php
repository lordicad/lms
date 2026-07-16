<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\LeaderboardService;
use App\Services\TrendingService;
use App\Support\ActiveGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function __invoke(Request $request, LeaderboardService $leaderboard, TrendingService $trending): View
    {
        $user = $request->user();
        $grade = ActiveGrade::for($user);

        if (! $grade) {
            return view('belajar.index', ['user' => $user, 'grade' => null]);
        }

        $gradeId = $grade->id;
        $myRow = $leaderboard->rowFor($user);

        $startedIds = LessonProgress::where('student_id', $user->id)->pluck('lesson_id');

        // Continue watching: genuinely started, not finished, most recent first.
        $continue = Lesson::published()
            ->join('lesson_progress', 'lesson_progress.lesson_id', '=', 'lessons.id')
            ->where('lesson_progress.student_id', $user->id)
            ->where('lesson_progress.completed', false)
            ->whereBetween('lesson_progress.percent', [LessonProgress::RESUME_MIN, 89])
            ->orderByDesc('lesson_progress.last_watched_at')
            ->select('lessons.*')
            ->withStudentContext($user)
            ->limit(12)
            ->get();

        // Trending, with student context grafted on for the cards.
        $trend = $trending->forGrade($gradeId, 12);
        $trend['lessons']->load([
            'progress' => fn ($q) => $q->where('student_id', $user->id),
            'favourites' => fn ($q) => $q->where('student_id', $user->id),
        ]);

        $favourites = Lesson::published()
            ->join('favourites', 'favourites.lesson_id', '=', 'lessons.id')
            ->where('favourites.student_id', $user->id)
            ->orderByDesc('favourites.created_at')
            ->select('lessons.*')
            ->withStudentContext($user)
            ->limit(12)
            ->get();

        $newest = Lesson::published()
            ->whereHas('chapter', fn ($q) => $q->where('grade_id', $gradeId)->where('is_active', true))
            ->where('lessons.created_at', '>=', now()->subDays(30))
            ->withStudentContext($user)
            ->latest('id')
            ->limit(12)
            ->get();

        return view('belajar.index', [
            'user' => $user,
            'grade' => $grade,
            'points' => $myRow->points ?? 0,
            'rank' => $myRow->rank ?? null,
            'hero' => $continue->first() ?? $trend['lessons']->first(),
            'heroResuming' => $continue->isNotEmpty(),
            'subjects' => $grade->subjects()
                ->orderBy('sort_order')
                ->withCount(['lessons as lessons_count' => fn ($q) => $q
                    ->where('lessons.is_published', true)
                    ->where('chapters.grade_id', $gradeId)])
                ->get(),
            'continue' => $continue,
            'trending' => $trend['lessons'],
            'trendingFallback' => $trend['fallback'],
            'favourites' => $favourites,
            'newest' => $newest,
            'suggested' => $this->suggested($user, $gradeId, $startedIds),
        ]);
    }

    /**
     * "Mungkin Anda Suka": unstarted lessons in the subjects this student watches most. With no
     * history yet, unwatched lessons from their Tahun, shuffled deterministically for the day.
     */
    private function suggested($user, int $gradeId, $startedIds)
    {
        $topSubjectIds = DB::table('lesson_progress')
            ->join('lessons', 'lessons.id', '=', 'lesson_progress.lesson_id')
            ->join('chapters', 'chapters.id', '=', 'lessons.chapter_id')
            ->where('lesson_progress.student_id', $user->id)
            ->groupBy('chapters.subject_id')
            ->orderByRaw('count(*) desc')
            ->limit(3)
            ->pluck('chapters.subject_id');

        if ($topSubjectIds->isNotEmpty()) {
            $suggested = Lesson::published()
                ->whereHas('chapter', fn ($q) => $q->where('grade_id', $gradeId)->where('is_active', true)
                    ->whereIn('subject_id', $topSubjectIds))
                ->whereNotIn('id', $startedIds)
                ->withStudentContext($user)
                ->latest('id')
                ->limit(12)
                ->get();

            if ($suggested->isNotEmpty()) {
                return $suggested;
            }
        }

        // Deterministic daily shuffle so the picks feel fresh but stable within a day.
        return Lesson::published()
            ->whereHas('chapter', fn ($q) => $q->where('grade_id', $gradeId)->where('is_active', true))
            ->whereNotIn('id', $startedIds)
            ->withStudentContext($user)
            ->orderByRaw('RAND(?)', [(int) now()->format('Ymd')])
            ->limit(12)
            ->get();
    }
}
