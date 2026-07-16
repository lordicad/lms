<?php

namespace App\Http\Controllers\Api\Student;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Watch surface for the mobile app: lesson detail (video source + progress + neighbours),
 * the idempotent "viewed" ping, and throttled progress saves. Mirrors WatchController and
 * ProgressController as JSON.
 */
class LessonController extends StudentApiController
{
    public function show(Request $request, Lesson $lesson): JsonResponse
    {
        if (! $lesson->is_published) {
            return response()->json(['message' => 'Video tidak tersedia.'], 404);
        }

        $user = $request->user();
        $lesson->load('chapter.subject', 'chapter.grade', 'teacher', 'materials');

        $progress = $user->isStudent() ? $lesson->progressFor($user) : null;
        $previous = $lesson->previousInChapter();
        $next = $lesson->nextInChapter();

        return response()->json([
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'description' => $lesson->description,
                'source' => $lesson->source,
                'youtube_id' => $lesson->youtube_id,
                'video_url' => $lesson->videoUrl(),
                'embed_url' => $lesson->embedUrl(),
                'thumbnail_url' => $lesson->thumbnailUrl(),
                'duration_seconds' => $lesson->duration_seconds,
                'teacher_name' => $lesson->teacher?->name,
            ],
            'chapter' => [
                'id' => $lesson->chapter->id,
                'label' => $lesson->chapter->label(),
            ],
            'subject' => $this->subjectCard($lesson->chapter->subject),
            'grade' => $this->gradePayload($lesson->chapter->grade),
            'progress' => $progress ? [
                'position_seconds' => $progress->position_seconds,
                'percent' => $progress->percent,
                'completed' => (bool) $progress->completed,
            ] : null,
            'favourited' => $user->isStudent() && $lesson->isFavouritedBy($user),
            'previous' => $previous ? ['id' => $previous->id, 'title' => $previous->title] : null,
            'next' => $next ? ['id' => $next->id, 'title' => $next->title] : null,
            'materials' => $lesson->materials->map(fn ($m) => $this->materialCard($m))->all(),
        ]);
    }

    public function markViewed(Request $request, Lesson $lesson): JsonResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return response()->json(['counted' => false]);
        }

        $view = LessonView::firstOrCreate([
            'lesson_id' => $lesson->id,
            'student_id' => $user->id,
        ]);

        if ($view->wasRecentlyCreated) {
            $lesson->increment('views_count');
        }

        return response()->json([
            'counted' => $view->wasRecentlyCreated,
            'views' => $lesson->fresh()->views_count,
        ]);
    }

    public function saveProgress(Request $request, Lesson $lesson): JsonResponse
    {
        $data = $request->validate([
            'position_seconds' => ['required', 'integer', 'min:0', 'max:360000'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:360000'],
        ]);

        $student = $request->user();

        if (! $student->isStudent()) {
            return response()->json(['saved' => false]);
        }

        $progress = LessonProgress::firstOrNew([
            'student_id' => $student->id,
            'lesson_id' => $lesson->id,
        ]);

        $position = $data['position_seconds'];
        $duration = $data['duration_seconds'] ?? $progress->duration_seconds;

        if ($duration !== null && $position > $duration) {
            throw ValidationException::withMessages([
                'position_seconds' => __('Kedudukan melebihi tempoh video.'),
            ]);
        }

        $percent = $duration ? min(100, (int) round($position / $duration * 100)) : $progress->percent;

        $progress->fill([
            'position_seconds' => $position,
            'duration_seconds' => $duration,
            'percent' => $percent,
            'completed' => $progress->completed || $percent >= LessonProgress::COMPLETE_AT,
            'last_watched_at' => now(),
        ])->save();

        if ($duration && ! $lesson->duration_seconds) {
            $lesson->forceFill(['duration_seconds' => $duration])->save();
        }

        return response()->json([
            'saved' => true,
            'percent' => $progress->percent,
            'completed' => (bool) $progress->completed,
        ]);
    }
}
