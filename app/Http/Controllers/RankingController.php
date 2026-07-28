<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Services\LeaderboardService;
use App\Support\ActiveGrade;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * Student leaderboard. The Tahun follows the header's Tahun switcher (ActiveGrade), so the
     * board matches whatever year the student is browsing elsewhere; the Subjek filter narrows it
     * further. Ranks stay continuous and absolute because LeaderboardService ranks the full set
     * before applying the limit.
     */
    public function __invoke(Request $request, LeaderboardService $leaderboard): View
    {
        $user = $request->user();

        $grade = ActiveGrade::for($user);

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
            'grade' => $grade,
        ]);
    }
}
