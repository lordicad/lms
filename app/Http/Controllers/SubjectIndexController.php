<?php

namespace App\Http\Controllers;

use App\Support\ActiveGrade;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectIndexController extends Controller
{
    /**
     * All subjects offered in the student's active Tahun, grouped by Kurikulum 2027 category.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $grade = ActiveGrade::for($user);

        return view('belajar.subjek-index', [
            'grade' => $grade,
            'subjectsByCategory' => $grade ? $grade->subjectsByCategory() : collect(),
        ]);
    }
}
