<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Subject;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * Student leaderboard. A Tahun and (optional) Subjek are chosen from the filters: the student
     * defaults to their own Tahun but can look at any year's board, on its own or narrowed to a
     * subject. Ranks stay continuous and absolute because LeaderboardService ranks the full set
     * before applying the limit.
     */
    public function __invoke(Request $request, LeaderboardService $leaderboard): View
    {
        $user = $request->user();

        $grades = Grade::orderBy('level')->get();

        // The Tahun being viewed — the chosen one, falling back to the student's own.
        $grade = ($request->filled('tahun')
            ? $grades->firstWhere('level', $request->integer('tahun'))
            : null) ?? $user->grade;

        $subject = $request->filled('subjek')
            ? Subject::where('slug', $request->string('subjek'))->first()
            : null;

        $top = $leaderboard->ranking(
            gradeId: $grade?->id,
            subjectId: $subject?->id,
            limit: 100,
        );

        // "Your rank" only means something on the student's own Tahun; on another year's board
        // they are not a competitor, so the sticky row is hidden.
        $onOwnGrade = $grade && $grade->id === $user->grade_id;
        $myRow = $onOwnGrade ? $leaderboard->rowFor($user, $subject?->id) : null;

        return view('ranking.index', [
            'top' => $top,
            'myRow' => $myRow,
            // Pin the student's own row below the table when they are outside the top 100.
            'showMyRow' => $myRow && ! $top->contains(fn ($row) => $row->student->id === $user->id),
            'subjects' => Subject::orderBy('sort_order')->get(),
            'subject' => $subject,
            'grades' => $grades,
            'grade' => $grade,
        ]);
    }
}
