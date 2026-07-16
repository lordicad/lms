<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Material;
use App\Support\ActiveGrade;
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
        $grade = ActiveGrade::for($user);

        $lessons = $grade
            ? Lesson::published()
                ->whereHas('chapter', fn ($q) => $q->where('grade_id', $grade->id)->where('is_active', true))
                ->withStudentContext($user)
                ->orderByDesc('id')
                ->get()
            : collect();

        $materials = $grade
            ? Material::query()
                ->whereHas('chapter', fn ($q) => $q->where('grade_id', $grade->id)->where('is_active', true))
                ->with('chapter.subject')
                ->orderBy('chapter_id')
                ->get()
                ->groupBy('chapter_id')
            : collect();

        return view('belajar.simpanan', [
            'grade' => $grade,
            'lessons' => $lessons,
            'materialsByChapter' => $materials,
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
