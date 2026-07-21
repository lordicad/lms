<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Support\ContentFilter;
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
        $filter = ContentFilter::fromRequest($request);
        // Rebuilt per call: the summary counts each need their own query, and reusing one
        // builder would stack their wheres on top of each other.
        $filtered = fn (): Builder => $filter->apply(Lesson::query());

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
            'filter' => $filter,
        ]);
    }

    public function material(Request $request): View
    {
        $filter = ContentFilter::fromRequest($request);
        $filtered = fn (): Builder => $filter->apply(Material::query());

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
            'filter' => $filter,
        ]);
    }

    public function quiz(Request $request): View
    {
        $filter = ContentFilter::fromRequest($request);
        $filtered = fn (): Builder => $filter->apply(Quiz::query());

        $quizzes = $filtered()
            ->with([
                'chapter.subject',
                'chapter.grade',
                'teacher',
                // Questions ride along so the preview dialog needs no second request. Only
                // interactive quizzes have any; a file quiz is a document, not a question set.
                'questions.options',
            ])
            ->withCount([
                'attempts as attempts_count' => fn (Builder $q) => $q->completed(),
                'attempts as pass_count' => fn (Builder $q) => $q->completed()->passed(),
            ])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // Attempts are counted through the quiz, so the same Subjek/Tahun filter applies and the
        // cards keep describing the rows on screen. Every completed attempt counts, retries
        // included: this reports usage, not standings.
        $attempts = fn (): Builder => QuizAttempt::query()
            ->completed()
            ->whereHas('quiz', fn (Builder $q) => $filter->apply($q));

        $totalAttempts = $attempts()->count();
        $passCount = $attempts()->passed()->count();

        return view('admin.kandungan.kuiz', [
            'quizzes' => $quizzes,
            'totalCount' => $filtered()->count(),
            'attemptCount' => $totalAttempts,
            'passCount' => $passCount,
            'failCount' => $totalAttempts - $passCount,
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'filter' => $filter,
        ]);
    }

}
