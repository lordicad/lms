<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Support\ActiveGrade;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Search lessons by title/description, strictly within the student's accessible scope:
     * their active Tahun, active chapters (so only offered subjects), published only.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $grade = ActiveGrade::for($user);

        $query = trim((string) $request->query('q', ''));

        $results = collect();

        if ($grade && $query !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $query).'%';

            $results = Lesson::published()
                ->whereHas('chapter', fn ($q) => $q->where('grade_id', $grade->id)->where('is_active', true))
                ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('description', 'like', $like))
                ->withStudentContext($user)
                ->orderByDesc('id')
                ->limit(48)
                ->get();
        }

        return view('belajar.cari', [
            'grade' => $grade,
            'query' => $query,
            'results' => $results,
        ]);
    }
}
