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
        $filter = \App\Support\ContentFilter::fromRequest($request);

        $materials = $filter->apply(
            $request->user()->materials()->with('chapter.subject', 'chapter.grade', 'lesson')
        )
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('cikgu.bahan.index', [
            'materials' => $materials,
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'filter' => $filter,
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

        $file = $request->file('file');

        $material = Material::create([
            'chapter_id' => $request->integer('chapter_id'),
            'lesson_id' => $request->input('lesson_id') ?: null,
            'teacher_id' => $request->user()->id,
            'title' => $request->input('title'),
            'file_path' => Uploads::store($file, 'materials'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_kb' => Uploads::sizeKb($file),
        ]);

        return redirect()
            ->route('cikgu.bahan.index')
            ->with('status', __('Bahan ":title" berjaya dimuat naik.', ['title' => $material->title]));
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

        $oldPath = $material->file_path;

        $material->fill([
            'chapter_id' => $request->integer('chapter_id'),
            'lesson_id' => $request->input('lesson_id') ?: null,
            'title' => $request->input('title'),
        ]);

        $replaced = false;

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            $material->fill([
                'file_path' => Uploads::store($file, 'materials'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_kb' => Uploads::sizeKb($file),
            ]);

            $replaced = true;
        }

        $material->save();

        if ($replaced) {
            Storage::disk('uploads')->delete($oldPath);
        }

        return redirect()
            ->route('cikgu.bahan.index')
            ->with('status', __('Bahan ":title" berjaya dikemas kini.', ['title' => $material->title]));
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
