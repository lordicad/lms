<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Subject;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MOE oversight of the content library itself, across every teacher. Read-only, like the rest of
 * the admin surface: an admin can see and open a lesson, but never edit or remove one.
 */
class AdminContentController extends Controller
{
    public function video(Request $request): View
    {
        // Both filters are independent `when()`s, so subject alone, Tahun alone, or the two
        // together all narrow the same query. The summary counts reuse it, so the cards always
        // describe the rows actually on screen rather than the whole library.
        $filtered = fn (): Builder => Lesson::query()
            ->when($request->filled('subjek'), fn (Builder $q) => $q->whereHas(
                'chapter.subject',
                fn (Builder $s) => $s->where('slug', $request->string('subjek')),
            ))
            ->when($request->filled('tahun'), fn (Builder $q) => $q->whereHas(
                'chapter.grade',
                fn (Builder $g) => $g->where('level', $request->integer('tahun')),
            ));

        $lessons = $filtered()
            ->with('chapter.subject', 'chapter.grade', 'teacher')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.kandungan.video', [
            'lessons' => $lessons,
            'totalCount' => $filtered()->count(),
            'youtubeCount' => $filtered()->where('source', Lesson::SOURCE_YOUTUBE)->count(),
            'uploadCount' => $filtered()->where('source', Lesson::SOURCE_UPLOAD)->count(),
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
        ]);
    }
}
