<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Http\Requests\MaterialRequest;
use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Subject;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user();
        $filter = \App\Support\ContentFilter::fromRequest($request);

        $materials = $filter->apply(
            $teacher->materials()->with('chapter.subject', 'chapter.grade', 'lesson')
        )
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('cikgu.bahan.index', [
            'materials' => $materials,
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'filter' => $filter,
            // All-time count of materials uploaded by this teacher (not the filtered page count).
            'totalMaterials' => $teacher->materials()->count(),
        ]);
    }

    /**
     * Reached either from the Bahan tab, or from a lesson's page with ?lesson=, which
     * pre-fills the chapter and links the material to that video.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Material::class);

        $lesson = $request->filled('lesson')
            ? Lesson::with('chapter')->find($request->integer('lesson'))
            : null;

        // Only the owner may attach material to their own lesson.
        if ($lesson && $lesson->teacher_id !== $request->user()->id) {
            $lesson = null;
        }

        return view('cikgu.bahan.form', [
            'material' => new Material(['lesson_id' => $lesson?->id]),
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'chapter' => $lesson?->chapter,
            'lesson' => $lesson,
            'lessons' => $this->lessonsInChapter($request->user()->id, $lesson?->chapter_id),
        ]);
    }

    public function store(MaterialRequest $request): RedirectResponse
    {
        $this->authorize('create', Material::class);

        $created = $this->createMaterials($request, $request->integer('chapter_id'), $request->input('lesson_id') ?: null);

        $status = count($created) === 1
            ? __('Bahan ":title" berjaya dimuat naik.', ['title' => $created[0]->title])
            : __(':count bahan berjaya dimuat naik.', ['count' => count($created)]);

        return redirect()->route('cikgu.bahan.index')->with('status', $status);
    }

    /**
     * Create one material per uploaded files[] entry in the chapter, titles paired by position -
     * a blank title falls back to the file's own name with the extension dropped. Shared by
     * store() and update(): the edit page can also drop in more files, each a new material.
     *
     * @return array<int, Material>
     */
    private function createMaterials(MaterialRequest $request, int $chapterId, ?int $lessonId): array
    {
        $titles = $request->input('titles', []);
        $created = [];

        foreach ($request->file('files', []) as $index => $file) {
            $given = trim((string) ($titles[$index] ?? ''));

            $created[] = Material::create([
                'chapter_id' => $chapterId,
                'lesson_id' => $lessonId,
                'teacher_id' => $request->user()->id,
                'title' => $given !== '' ? $given : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'file_path' => Uploads::store($file, 'materials'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_kb' => Uploads::sizeKb($file),
            ]);
        }

        return $created;
    }

    public function edit(Request $request, Material $material): View
    {
        $this->authorize('update', $material);

        $material->load('chapter.subject', 'chapter.grade');

        return view('cikgu.bahan.form', [
            'material' => $material,
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'chapter' => $material->chapter,
            'lesson' => $material->lesson,
            'lessons' => $this->lessonsInChapter($request->user()->id, $material->chapter_id),
        ]);
    }

    public function update(MaterialRequest $request, Material $material): RedirectResponse
    {
        $this->authorize('update', $material);

        $chapterId = $request->integer('chapter_id');
        $lessonId = $request->input('lesson_id') ?: null;
        $deleting = $request->boolean('delete_material');

        if ($deleting) {
            // The teacher chose to remove this material outright (its file goes with it).
            $material->deleteFile();
            $material->delete();
        } else {
            $material->update([
                'chapter_id' => $chapterId,
                'lesson_id' => $lessonId,
                'title' => $request->input('title'),
            ]);
        }

        // Any files dropped on the edit page become new materials in the chapter.
        $added = count($this->createMaterials($request, $chapterId, $lessonId));

        $status = match (true) {
            $deleting && $added > 0 => __('Bahan lama dipadam; :count fail baharu ditambah.', ['count' => $added]),
            $deleting => __('Bahan dipadam.'),
            $added > 0 => __('Bahan dikemas kini; :count fail baharu ditambah.', ['count' => $added]),
            default => __('Bahan ":title" berjaya dikemas kini.', ['title' => $material->title]),
        };

        return redirect()->route('cikgu.bahan.index')->with('status', $status);
    }

    public function destroy(Material $material): RedirectResponse
    {
        $this->authorize('delete', $material);

        $title = $material->title;

        $material->deleteFile();
        $material->delete();

        return redirect()
            ->route('cikgu.bahan.index')
            ->with('status', __('Bahan ":title" telah dipadam.', ['title' => $title]));
    }

    /**
     * The teacher's own lessons inside a chapter, for the optional "attach to video" select.
     *
     * @return \Illuminate\Support\Collection<int, Lesson>
     */
    private function lessonsInChapter(int $teacherId, ?int $chapterId)
    {
        if (! $chapterId) {
            return collect();
        }

        return Lesson::where('chapter_id', $chapterId)
            ->where('teacher_id', $teacherId)
            ->orderBy('title')
            ->get();
    }
}
