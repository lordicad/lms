<?php

namespace App\Http\Controllers;

use App\Support\ActiveGrade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

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

        // Return to where they were. If that URL pins a ?tahun= (e.g. the Subject page, which
        // honours it over the session), rewrite it to the chosen Tahun — otherwise the old
        // ?tahun would win and the page would ignore the switch.
        $back = URL::previous();
        $parts = parse_url($back);
        parse_str($parts['query'] ?? '', $query);

        if (array_key_exists('tahun', $query)) {
            $query['tahun'] = $level;
            $back = ($parts['path'] ?? '/').'?'.http_build_query($query);
        }

        return redirect($back);
    }
}
