<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * Student leaderboard. Always scoped to their own Tahun: a Tahun 3 pupil is never ranked
     * against a Tahun 6 pupil, and never sees their names.
     */
    public function __invoke(Request $request, LeaderboardService $leaderboard): View
    {
        $user = $request->user();

        $subject = $request->filled('subjek')
            ? Subject::where('slug', $request->string('subjek'))->first()
            : null;

        // Top 100 within the student's own Tahun (brief §3.2). Ranks stay continuous and absolute
        // because LeaderboardService ranks the full set before applying the limit.
        $top = $leaderboard->ranking(
            gradeId: $user->grade_id,
            subjectId: $subject?->id,
            limit: 100,
        );

        $myRow = $leaderboard->rowFor($user, $subject?->id);

        return view('ranking.index', [
            'top' => $top,
            'myRow' => $myRow,
            // Pin the student's own row below the table when they are outside the top 100.
            'showMyRow' => $myRow && ! $top->contains(fn ($row) => $row->student->id === $user->id),
            'subjects' => Subject::orderBy('sort_order')->get(),
            'subject' => $subject,
            'grade' => $user->grade,
        ]);
    }
}
