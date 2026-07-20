<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Subject;
use App\Rules\ValidSubjectGradeCombo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChapterController extends Controller
{
    /**
     * Read-only chapter navigation: pick a Tahun and a Subject, then open a Bab to see your own
     * content in it. Chapters are shared curriculum taxonomy — they are not created, renamed or
     * deleted here any more (brief §2.2).
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

        // Only count the signed-in teacher's own content in each Bab — this is their studio view.
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

        return view('cikgu.bab.index', [
            'subjects' => $subjects,
            'grades' => $grades,
            'subject' => $subject,
            'grade' => $grade,
            'chapters' => $chapters,
            'availability' => Subject::availabilityMap(),
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
