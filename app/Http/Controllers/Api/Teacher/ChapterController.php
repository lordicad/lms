<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Teacher Bab management for the mobile app: pick a Subject + Tahun, then add, rename or
 * remove a Bab. Mirrors the web Cikgu\ChapterController rules — the Bab number is assigned
 * (never typed), a Bab may only be added to an offered pair, and only an empty Bab is
 * deletable. Chapters are shared curriculum, so any teacher may manage them.
 */
class ChapterController extends Controller
{
    /** Subjects + Tahun for the dependent pickers on teacher forms. */
    public function options(Request $request): JsonResponse
    {
        if (! $this->isTeacher($request)) {
            return $this->forbidden();
        }

        return response()->json([
            'subjects' => Subject::orderBy('sort_order')->get()->map(fn ($s) => [
                'id' => $s->id,
                'slug' => $s->slug,
                'name' => $s->displayName(),
            ])->all(),
            'grades' => Grade::orderBy('level')->get()->map(fn ($g) => [
                'id' => $g->id,
                'level' => $g->level,
                'name' => $g->name,
            ])->all(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        if (! $this->isTeacher($request)) {
            return $this->forbidden();
        }

        $data = $request->validate([
            'subject_id' => ['required', 'integer'],
            'grade_id' => ['required', 'integer'],
        ]);

        $teacherId = $request->user()->id;
        $chapters = Chapter::where('subject_id', $data['subject_id'])
            ->where('grade_id', $data['grade_id'])
            ->ordered()
            ->withCount([
                'lessons as lessons_count' => fn ($query) => $query->where('teacher_id', $teacherId),
                'materials as materials_count' => fn ($query) => $query->where('teacher_id', $teacherId),
                'quizzes as quizzes_count' => fn ($query) => $query->where('teacher_id', $teacherId),
            ])
            ->get();

        return response()->json([
            'is_offered' => $this->offered($data['subject_id'], $data['grade_id']),
            'chapters' => $chapters->map(fn ($c) => [
                'id' => $c->id,
                'number' => $c->number,
                'title' => $c->title,
                'description' => $c->description,
                'lessons_count' => $c->lessons_count,
                'materials_count' => $c->materials_count,
                'quizzes_count' => $c->quizzes_count,
                'is_empty' => ($c->lessons_count + $c->materials_count + $c->quizzes_count) === 0,
            ])->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->isTeacher($request)) {
            return $this->forbidden();
        }

        $data = $request->validate([
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'grade_id' => ['required', 'integer', Rule::exists('grades', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $this->offered($data['subject_id'], $data['grade_id'])) {
            return response()->json(['message' => 'Subjek ini tidak ditawarkan untuk Tahun tersebut.'], 422);
        }

        $next = (int) Chapter::where('subject_id', $data['subject_id'])
            ->where('grade_id', $data['grade_id'])
            ->max('number') + 1;

        $chapter = Chapter::create([
            ...$data,
            'number' => $next,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['id' => $chapter->id, 'number' => $chapter->number], 201);
    }

    public function update(Request $request, Chapter $chapter): JsonResponse
    {
        if (! $this->isTeacher($request)) {
            return $this->forbidden();
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $chapter->update($data);

        return response()->json(['updated' => true]);
    }

    public function destroy(Request $request, Chapter $chapter): JsonResponse
    {
        if (! $this->isTeacher($request)) {
            return $this->forbidden();
        }

        if (! $chapter->isEmpty()) {
            return response()->json([
                'message' => 'Bab tidak boleh dipadam kerana masih ada kandungan. Padam video, bahan dan kuiz di dalamnya dahulu.',
            ], 422);
        }

        $chapter->delete();

        return response()->json(['deleted' => true]);
    }

    private function offered(int $subjectId, int $gradeId): bool
    {
        return DB::table('grade_subject')
            ->where('subject_id', $subjectId)
            ->where('grade_id', $gradeId)
            ->exists();
    }

    private function isTeacher(Request $request): bool
    {
        return $request->user()?->isTeacher() ?? false;
    }

    private function forbidden(): JsonResponse
    {
        return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
    }
}
