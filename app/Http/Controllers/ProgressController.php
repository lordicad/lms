<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ProgressController extends Controller
{
    /**
     * Save a watch-progress ping from the player. Fired every ~10s while playing, on pause,
     * on end, and on the way out via sendBeacon — so it must stay cheap and idempotent.
     *
     * The row is always keyed by the authenticated student, so no one can write another
     * student's progress. lesson_views (the teacher-facing "watched once" counter) is left to
     * the existing first-play call and never touched here, so nothing is double counted.
     */
    public function store(Request $request, Lesson $lesson): Response
    {
        $this->authorize('view', $lesson);

        $data = $request->validate([
            'position_seconds' => ['required', 'integer', 'min:0', 'max:360000'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:360000'],
        ]);

        $student = $request->user();

        $progress = LessonProgress::firstOrNew([
            'student_id' => $student->id,
            'lesson_id' => $lesson->id,
        ]);

        $position = $data['position_seconds'];
        $duration = $data['duration_seconds'] ?? $progress->duration_seconds;

        // 0 <= position <= duration.
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
            // Sticky: once finished it stays finished, so a replay never drags it back into
            // Continue Watching.
            'completed' => $progress->completed || $percent >= LessonProgress::COMPLETE_AT,
            'last_watched_at' => now(),
        ])->save();

        // Backfill the lesson's own duration the first time a real one arrives, so cards can
        // show a runtime without every student re-measuring it.
        if ($duration && ! $lesson->duration_seconds) {
            $lesson->forceFill(['duration_seconds' => $duration])->save();
        }

        return response()->noContent();
    }
}
