<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Subject;
use App\Rules\ValidSubjectGradeCombo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChapterController extends Controller
{
    /**
     * Chapter management: pick a Subject and a Tahun, then rename, add or remove a Bab.
     */
    public function index(Request $request): View
    {
        $subjects = Subject::orderBy('sort_order')->get();
        $grades = Grade::orderBy('level')->get();

        $subject = $request->filled('subjek')
            ? $subjects->firstWhere('slug', $request->string('subjek')->toString())
            : $subjects->first();

        $grade = $request->filled('tahun')
            ? $grades->firstWhere('level', $request->integer('tahun'))
            : $grades->first();

        $teacher = $request->user();

        // Counts are scoped to the signed-in teacher, so a card's numbers match what the Bab's
        // View page shows (only that teacher's own content).
        $chapters = ($subject && $grade)
            ? Chapter::where('subject_id', $subject->id)
                ->where('grade_id', $grade->id)
                ->ordered()
                ->withCount([
                    'lessons as lessons_count' => fn ($q) => $q->where('teacher_id', $teacher->id),
                    'materials as materials_count' => fn ($q) => $q->where('teacher_id', $teacher->id),
                    'quizzes as quizzes_count' => fn ($q) => $q->where('teacher_id', $teacher->id),
                ])
                ->get()
            : collect();

        // Whether this Subject is offered in this Tahun under Kurikulum 2027. A Bab may only be
        // added to an offered pair; otherwise the page explains why and hides the add form.
        $isOffered = ($subject && $grade) && DB::table('grade_subject')
            ->where('subject_id', $subject->id)
            ->where('grade_id', $grade->id)
            ->exists();

        return view('cikgu.bab.index', [
            'subjects' => $subjects,
            'grades' => $grades,
            'subject' => $subject,
            'grade' => $grade,
            'chapters' => $chapters,
            'isOffered' => $isOffered,
            'availability' => Subject::availabilityMap(),
            'nextNumber' => ($chapters->max('number') ?? 0) + 1,
        ]);
    }

    /**
     * A single Bab: everything the signed-in teacher has uploaded into it, grouped into Videos,
     * Materials and Quizzes. Other teachers' content is never shown — ownership is enforced here on
     * the server, not just by hiding a button.
     */
    public function show(Request $request, Chapter $chapter): View
    {
        $chapter->load('subject', 'grade');
        $teacher = $request->user();

        $lessons = $chapter->lessons()
            ->where('teacher_id', $teacher->id)
            ->latest('id')
            ->get();

        $materials = $chapter->materials()
            ->where('teacher_id', $teacher->id)
            ->latest('id')
            ->get();

        $quizzes = $chapter->quizzes()
            ->where('teacher_id', $teacher->id)
            ->withCount(['questions', 'completedAttempts as completed_attempts_count'])
            ->latest('id')
            ->get();

        return view('cikgu.bab.show', [
            'chapter' => $chapter,
            'subject' => $chapter->subject,
            'grade' => $chapter->grade,
            'lessons' => $lessons,
            'materials' => $materials,
            'quizzes' => $quizzes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Chapter::class);

        $validated = $request->validate([
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'grade_id' => ['required', 'integer', Rule::exists('grades', 'id'), ValidSubjectGradeCombo::forPair()],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'title.required' => __('Sila isi tajuk Bab.'),
        ]);

        // The Bab number is assigned, never typed: it is always the next one in this
        // Subject x Tahun, which keeps the unique key intact.
        $next = (int) Chapter::where('subject_id', $validated['subject_id'])
            ->where('grade_id', $validated['grade_id'])
            ->max('number') + 1;

        $chapter = Chapter::create([
            ...$validated,
            'number' => $next,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', __('Bab :number berjaya ditambah.', ['number' => $chapter->number]));
    }

    public function edit(Chapter $chapter): View
    {
        $this->authorize('update', $chapter);

        $chapter->load('subject', 'grade');

        return view('cikgu.bab.form', ['chapter' => $chapter]);
    }

    public function update(Request $request, Chapter $chapter): RedirectResponse
    {
        $this->authorize('update', $chapter);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'title.required' => __('Sila isi tajuk Bab.'),
        ]);

        $chapter->update($validated);

        return redirect()
            ->route('cikgu.bab.index', [
                'subjek' => $chapter->subject->slug,
                'tahun' => $chapter->grade->level,
            ])
            ->with('status', __('Bab :number berjaya dikemas kini.', ['number' => $chapter->number]));
    }

    public function destroy(Chapter $chapter): RedirectResponse
    {
        // ChapterPolicy::delete only passes when the Bab holds no lessons, materials or quizzes.
        if (! $chapter->isEmpty()) {
            return back()->with('error', __('Bab :number tidak boleh dipadam kerana masih ada kandungan di dalamnya. Padam video, bahan dan kuiz di dalam bab ini dahulu.', ['number' => $chapter->number]));
        }

        $this->authorize('delete', $chapter);

        $number = $chapter->number;
        $chapter->delete();

        return back()->with('status', __('Bab :number telah dipadam.', ['number' => $number]));
    }

    /**
     * JSON for the dependent Subject -> Tahun -> Bab dropdowns on every teacher form.
     */
    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'integer'],
            'grade' => ['required', 'integer', new ValidSubjectGradeCombo('pair', 'subject', 'grade')],
        ]);

        // New content only ever goes into active chapters of an offered pair.
        $chapters = Chapter::where('subject_id', $validated['subject'])
            ->where('grade_id', $validated['grade'])
            ->active()
            ->ordered()
            ->get(['id', 'number', 'title']);

        return response()->json(
            $chapters->map(fn (Chapter $chapter) => [
                'id' => $chapter->id,
                'label' => "Bab {$chapter->number}: {$chapter->title}",
            ])->values(),
        );
    }
}
