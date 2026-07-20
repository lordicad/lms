<?php

namespace App\Http\Controllers\Api\Student;

use App\Models\Lesson;
use App\Models\Material;
use App\Models\TeacherNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Mobile counterpart of the live web "Simpanan Offline" page. Only uploaded
 * videos can honestly be downloaded; YouTube content remains online-only.
 */
class OfflineController extends StudentApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user?->isStudent()) {
            return response()->json(['message' => 'Hanya murid boleh mengakses simpanan offline.'], 403);
        }

        $grade = $this->resolveGrade($request, $user);

        if (! $grade) {
            return response()->json(['grade' => null, 'lessons' => [], 'materials' => []]);
        }

        $level = $grade->level;
        $lessons = Lesson::published()
            ->whereHas('chapter', fn ($query) => $query
                ->where('grade_id', $grade->id)
                ->where('is_active', true))
            ->with('chapter.subject')
            ->latest('id')
            ->get();
        $materials = Material::query()
            ->whereHas('chapter', fn ($query) => $query
                ->where('grade_id', $grade->id)
                ->where('is_active', true))
            ->with('chapter.subject')
            ->orderBy('chapter_id')
            ->get();

        return response()->json([
            'grade' => $this->gradePayload($grade),
            'lessons' => $lessons->map(fn (Lesson $lesson) => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'source' => $lesson->source,
                'thumbnail_url' => $lesson->thumbnailUrl(),
                'subject_name' => $lesson->chapter?->subject?->displayName(),
                'chapter_label' => $lesson->chapter?->label(),
                'downloadable' => $lesson->isUpload() && (bool) $lesson->video_path,
                'download_url' => $lesson->isUpload() && $lesson->video_path
                    ? url("/api/student/offline/lessons/{$lesson->id}?grade={$level}")
                    : null,
                'file_name' => $lesson->video_path
                    ? Str::slug($lesson->title).'.'.(pathinfo($lesson->video_path, PATHINFO_EXTENSION) ?: 'mp4')
                    : null,
            ])->all(),
            'materials' => $materials->map(fn (Material $material) => [
                'id' => $material->id,
                'title' => $material->title,
                'extension' => $material->extension(),
                'human_size' => $material->humanSize(),
                'subject_name' => $material->chapter?->subject?->displayName(),
                'chapter_label' => $material->chapter?->label(),
                'download_url' => url("/api/student/offline/materials/{$material->id}?grade={$level}"),
                'file_name' => $material->original_name,
            ])->all(),
        ]);
    }

    public function downloadLesson(Request $request, Lesson $lesson): StreamedResponse
    {
        $chapter = $this->availableChapter($request, $lesson->chapter_id);

        abort_unless($lesson->is_published && $lesson->isUpload() && $lesson->video_path, Response::HTTP_NOT_FOUND);
        abort_if($chapter === null, Response::HTTP_NOT_FOUND);

        $disk = Storage::disk('uploads');
        abort_unless($disk->exists($lesson->video_path), Response::HTTP_NOT_FOUND);

        $extension = pathinfo($lesson->video_path, PATHINFO_EXTENSION) ?: 'mp4';

        return $disk->download($lesson->video_path, Str::slug($lesson->title).'.'.$extension);
    }

    public function downloadMaterial(Request $request, Material $material): StreamedResponse
    {
        $chapter = $this->availableChapter($request, $material->chapter_id);

        abort_if($chapter === null, Response::HTTP_NOT_FOUND);

        $disk = Storage::disk('uploads');
        abort_unless($material->file_path && $disk->exists($material->file_path), Response::HTTP_NOT_FOUND);

        $material->increment('download_count');
        TeacherNotification::record(
            $material->teacher_id,
            $request->user(),
            TeacherNotification::TYPE_DOWNLOAD,
            $material->title,
            route('cikgu.bahan.index'),
        );

        return $disk->download($material->file_path, $material->original_name);
    }

    /** Returns the currently browsed Tahun only when the content belongs to it. */
    private function availableChapter(Request $request, int $chapterId): ?object
    {
        $user = $request->user();

        if (! $user?->isStudent()) {
            return null;
        }

        $grade = $this->resolveGrade($request, $user);

        if (! $grade) {
            return null;
        }

        return \App\Models\Chapter::whereKey($chapterId)
            ->where('grade_id', $grade->id)
            ->where('is_active', true)
            ->first();
    }
}
