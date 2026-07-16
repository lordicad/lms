<?php

namespace App\Http\Controllers;

use App\Support\ActiveGrade;
use Illuminate\Http\RedirectResponse;

class TahunController extends Controller
{
    /**
     * Switch the Tahun a student is browsing (revision / preview). Persists in the session and
     * returns them to wherever they were.
     */
    public function __invoke(int $level): RedirectResponse
    {
        abort_unless($level >= 1 && $level <= 6, 404);

        ActiveGrade::set($level);

        return back();
    }
}
