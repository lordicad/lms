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
use App\Support\SchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $search = trim((string) $request->query('q', ''));

        // Preset published-date window, matched against created_at (the "Published" column). Both
        // ends are null when no preset is chosen, so the range simply does not narrow anything.
        [$from, $to] = $this->dateRange($request->query('tarikh'));

        // Rebuilt per call: the summary counts each need their own query, and reusing one
        // builder would stack their wheres on top of each other. Search + date ride along, so the
        // cards keep describing the rows on screen rather than the unfiltered library.
        $filtered = fn (): Builder => $this->narrow(
            $filter->apply(SchoolScope::content(Lesson::query())), $search, $from, $to
        );

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
            'search' => $search,
        ]);
    }

    /**
     * Narrow content by a free-text term, matched against its own title and the name of the
     * teacher who posted it — the two things an admin scanning this table actually knows.
     *
     * @param  Builder<Lesson>|Builder<Material>|Builder<Quiz>  $query
     * @return Builder<Lesson>|Builder<Material>|Builder<Quiz>
     */
    private function searched(Builder $query, string $search): Builder
    {
        return $query->when($search !== '', fn (Builder $q) => $q->where(
            fn (Builder $w) => $w
                ->where('title', 'like', "%{$search}%")
                ->orWhereHas('teacher', fn (Builder $t) => $t->where('name', 'like', "%{$search}%")),
        ));
    }

    /**
     * The free-text search and published-date window shared by all three content tables, applied on
     * top of the Subjek/Tahun filter. Kept in one place so Videos, Materials and Quizzes narrow the
     * same way and their summary cards keep describing the rows on screen.
     *
     * @param  Builder<Lesson>|Builder<Material>|Builder<Quiz>  $query
     * @return Builder<Lesson>|Builder<Material>|Builder<Quiz>
     */
    private function narrow(Builder $query, string $search, ?Carbon $from, ?Carbon $to): Builder
    {
        return $this->searched($query, $search)
            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('created_at', '<=', $to));
    }

    /**
     * The [from, to] datetime window for a preset published-date range, or [null, null] for no (or
     * an unknown) preset. Weeks start Monday; "today" and the rolling windows run up to the present
     * moment. Keys mirror the dropdown in the year-subject filter component.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function dateRange(?string $preset): array
    {
        $now = Carbon::now();

        return match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'last_7d' => [$now->copy()->subDays(6)->startOfDay(), $now],
            'this_month' => [$now->copy()->startOfMonth(), $now],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'last_30d' => [$now->copy()->subDays(29)->startOfDay(), $now],
            default => [null, null],
        };
    }

    public function material(Request $request): View
    {
        $filter = ContentFilter::fromRequest($request);
        $search = trim((string) $request->query('q', ''));
        [$from, $to] = $this->dateRange($request->query('tarikh'));

        $filtered = fn (): Builder => $this->narrow(
            $filter->apply(SchoolScope::content(Material::query())), $search, $from, $to
        );

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
            'search' => $search,
        ]);
    }

    public function quiz(Request $request): View
    {
        $filter = ContentFilter::fromRequest($request);
        $search = trim((string) $request->query('q', ''));
        [$from, $to] = $this->dateRange($request->query('tarikh'));

        $filtered = fn (): Builder => $this->narrow(
            $filter->apply(SchoolScope::content(Quiz::query())), $search, $from, $to
        );

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

        // Attempts are counted through the quiz, so the same Subjek/Tahun/search/date filter applies
        // and the cards keep describing the rows on screen. Every completed attempt counts, retries
        // included: this reports usage, not standings.
        $attempts = fn (): Builder => QuizAttempt::query()
            ->completed()
            ->whereHas('quiz', fn (Builder $q) => $this->narrow($filter->apply(SchoolScope::content($q)), $search, $from, $to));

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
            'search' => $search,
        ]);
    }

}
