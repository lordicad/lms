<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Services\TalentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A teacher's own talent signal: the four transparent sub-scores + headline, the per-lesson
 * breakdown with ownership badges, the connect card, and the disclaimer. Read-only.
 */
class TalentController extends Controller
{
    public function __invoke(Request $request, TalentService $talent): View
    {
        return view('cikgu.bakat', [
            'result' => $talent->forTeacher($request->user()),
        ]);
    }
}
