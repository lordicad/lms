<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Subject;
use App\Support\ActiveGrade;
use App\Support\ContentFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfflineController extends Controller
{
    /**
     * Simpanan Offline — honest offline: uploaded lessons can be downloaded as mp4; YouTube
     * lessons cannot (shown as online-only, never faked); and supporting materials (PDF/slides)
     * for the Tahun are listed so offline is useful even for YouTube lessons.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Year defaults to the student's active browsing Tahun, but a valid ?tahun overrides it.
        $grade = $request->filled('tahun')
            ? Grade::where('level', $request->integer('tahun'))->first()
            : ActiveGrade::for($user);

        // Subject depends on the Year; an invalid pair is dropped server-side.
        $filter = ContentFilter::forGrade($request, $grade);

        $lessons = $grade
            ? $filter->apply(
                Lesson::published()
                    ->whereHas('chapter', fn ($q) => $q->where('is_active', true))
                    ->withStudentContext($user)
                    ->with('teacher:id,name')
            )
                ->orderByDesc('id')
                ->get()
            : collect();

        $materials = $grade
            ? $filter->apply(
                Material::query()
                    ->whereHas('chapter', fn ($q) => $q->where('is_active', true))
                    ->with('chapter.subject', 'teacher:id,name')
            )
                ->orderBy('chapter_id')
                ->get()
                ->groupBy('chapter_id')
            : collect();

        return view('belajar.simpanan', [
            'grade' => $grade,
            'filter' => $filter,
            'lessons' => $lessons,
            'materialsByChapter' => $materials,
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
        ]);
    }

    /**
     * Stream an uploaded lesson's video as a download. YouTube lessons have no file to serve.
     */
    public function download(Request $request, Lesson $lesson): StreamedResponse
    {
        $this->authorize('view', $lesson);

        abort_unless($lesson->isUpload() && $lesson->video_path, 404);

        $disk = Storage::disk('uploads');

        abort_unless($disk->exists($lesson->video_path), 404);

        $extension = pathinfo($lesson->video_path, PATHINFO_EXTENSION) ?: 'mp4';

        return $disk->download($lesson->video_path, Str::slug($lesson->title).'.'.$extension);
    }
}
