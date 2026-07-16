<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\Subject;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherRankingController extends Controller
{
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

        $rows = $leaderboard->ranking(
            gradeId: $grade?->id,
            subjectId: $subject?->id,
            quizId: $quiz?->id,
        );

        // Quiz filter options follow the Tahun and Subjek already picked.
        $quizzes = Quiz::query()
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
