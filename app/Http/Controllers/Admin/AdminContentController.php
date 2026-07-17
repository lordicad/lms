<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
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
        // Rebuilt per call: the summary counts each need their own query, and reusing one
        // builder would stack their wheres on top of each other.
        $filtered = fn (): Builder => $this->filterByChapter(Lesson::query(), $request);

        $lessons = $filtered()
            ->with('chapter.subject', 'chapter.grade', 'teacher')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.kandungan.video', [
            'lessons' => $lessons,
            // Counts follow the filter, so the cards always describe the rows on screen.
            'totalCount' => $filtered()->count(),
            'youtubeCount' => $filtered()->where('source', Lesson::SOURCE_YOUTUBE)->count(),
            'uploadCount' => $filtered()->where('source', Lesson::SOURCE_UPLOAD)->count(),
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
        ]);
    }

    public function material(Request $request): View
    {
        $filtered = fn (): Builder => $this->filterByChapter(Material::query(), $request);

        $materials = $filtered()
            ->with('chapter.subject', 'chapter.grade', 'teacher')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // Type is derived from the stored filename — there is no extension column — so the
        // per-type counts match on that. The column's collation is case-insensitive, so a
        // NOTA.PDF is counted with the rest.
        $ofType = fn (string $ext): int => $filtered()->where('original_name', 'like', '%.'.$ext)->count();

        return view('admin.kandungan.bahan', [
            'materials' => $materials,
            'totalCount' => $filtered()->count(),
            'pdfCount' => $ofType('pdf'),
            'docxCount' => $ofType('docx'),
            'pptxCount' => $ofType('pptx'),
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
        ]);
    }

    /**
     * Subjek and Tahun narrow anything hanging off a chapter. They are separate `when()`s, so
     * subject alone, Tahun alone, or the two together all work without a special case.
     *
     * @param  Builder<Lesson>|Builder<Material>  $query
     * @return Builder<Lesson>|Builder<Material>
     */
    private function filterByChapter(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('subjek'), fn (Builder $q) => $q->whereHas(
                'chapter.subject',
                fn (Builder $s) => $s->where('slug', $request->string('subjek')),
            ))
            ->when($request->filled('tahun'), fn (Builder $q) => $q->whereHas(
                'chapter.grade',
                fn (Builder $g) => $g->where('level', $request->integer('tahun')),
            ));
    }
}
