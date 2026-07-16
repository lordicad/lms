<?php

namespace App\Support;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Support\Facades\Session;

/**
 * The Tahun a student is currently browsing. Defaults to their own grade, but they may switch
 * to another for revision or a preview; the choice persists in the session (not the URL) so it
 * follows them across every page until they change it.
 */
class ActiveGrade
{
    private const KEY = 'view_grade_level';

    public static function for(User $student): ?Grade
    {
        $level = Session::get(self::KEY);

        if ($level && ($grade = Grade::where('level', $level)->first())) {
            return $grade;
        }

        return $student->grade;
    }

    public static function set(int $level): void
    {
        Session::put(self::KEY, $level);
    }
}
