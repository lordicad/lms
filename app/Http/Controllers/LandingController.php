<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        // Someone already signed in has no use for the marketing page.
        if ($user = auth()->user()) {
            return redirect($user->homeRoute());
        }

        // The hero shows the Teras (core) subjects as a strip; the rest are summarised as a count,
        // since the full Kurikulum 2027 list of 27 across five categories is too many for a hero.
        $teras = Subject::where('category', 'teras')->orderBy('sort_order')->get();

        return view('landing', [
            'terasSubjects' => $teras,
            'moreSubjectCount' => Subject::count() - $teras->count(),
            'lessonCount' => Lesson::published()->count(),
            'quizCount' => Quiz::published()->count(),
        ]);
    }
}
