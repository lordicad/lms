<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Support\ActiveGrade;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectIndexController extends Controller
{
    /**
     * All subjects offered in a Tahun, grouped by Kurikulum 2027 category.
     * Honours an optional ?tahun= (so links like "All subjects" keep the year
     * being browsed); otherwise falls back to the student's active Tahun.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $grade = ($level = $request->integer('tahun'))
            ? Grade::where('level', $level)->first() ?? ActiveGrade::for($user)
            : ActiveGrade::for($user);

        return view('belajar.subjek-index', [
            'grade' => $grade,
            'subjectsByCategory' => $grade ? $grade->subjectsByCategory() : collect(),
        ]);
    }
}
