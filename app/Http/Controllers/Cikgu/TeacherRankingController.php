<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\Subject;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;

class TeacherRankingController extends Controller
{
    /** Fifty to a page: enough to scan a whole year group at once without an endless scroll. */
    private const PER_PAGE = 50;

    /**
     * The full table, not just the top 10, filterable by Tahun, Subjek and a single quiz.
     * Reads from the same LeaderboardService the students' /ranking uses, so the two views
     * can never disagree about who is first.
     */
    public function __invoke(Request $request, LeaderboardService $leaderboard): View
    {
        $grade = $request->filled('tahun')
            ? Grade::where('level', $request->integer('tahun'))->first()
            : null;

        $subject = $request->filled('subjek')
            ? Subject::where('slug', $request->string('subjek'))->first()
            : null;

        $quiz = $request->filled('kuiz')
            ? Quiz::find($request->integer('kuiz'))
            : null;

        // Scoped to this teacher's own quizzes: the board answers "how are my students doing on
        // the work I set", not "who is top of the whole platform".
        $ranked = $leaderboard->ranking(
            gradeId: $grade?->id,
            subjectId: $subject?->id,
            quizId: $quiz?->id,
            teacherId: $request->user()->id,
        );

        // Rank is stamped across the whole ranked set before this slice, so page two carries on at
        // 51 rather than restarting — the number is the student's real position, not a row counter.
        $page = Paginator::resolveCurrentPage();
        $rows = new LengthAwarePaginator(
            $ranked->forPage($page, self::PER_PAGE)->values(),
            $ranked->count(),
            self::PER_PAGE,
            $page,
            // Keeps Tahun / Subjek / Kuiz on the page links, so paging never drops the filter.
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()],
        );

        // Quiz filter options follow the Tahun and Subjek already picked — and only ever list the
        // teacher's own quizzes, so the dropdown cannot offer one the board would show nothing for.
        $quizzes = Quiz::query()
            ->where('teacher_id', $request->user()->id)
            ->where('type', Quiz::TYPE_INTERACTIVE)
            ->when($grade, fn ($q) => $q->whereHas('chapter', fn ($c) => $c->where('grade_id', $grade->id)))
            ->when($subject, fn ($q) => $q->whereHas('chapter', fn ($c) => $c->where('subject_id', $subject->id)))
            ->orderBy('title')
            ->get();

        return view('cikgu.ranking', [
            'rows' => $rows,
            'grades' => Grade::orderBy('level')->get(),
            'subjects' => Subject::orderBy('sort_order')->get(),
            'quizzes' => $quizzes,
            'grade' => $grade,
            'subject' => $subject,
            'quiz' => $quiz,
        ]);
    }
}
