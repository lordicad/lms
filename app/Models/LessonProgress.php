<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per (student, lesson): where the student is up to in a video, and whether they have
 * effectively finished it. Written by the throttled progress pings from the player; read by
 * Continue Watching, resume, and completion.
 */
class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    /** A lesson is "watched enough to count as done" at this percent. */
    public const COMPLETE_AT = 90;

    /** Continue Watching only shows lessons a student has genuinely started. */
    public const RESUME_MIN = 3;

    protected $fillable = [
        'student_id',
        'lesson_id',
        'position_seconds',
        'duration_seconds',
        'percent',
        'completed',
        'last_watched_at',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'lesson_id' => 'integer',
            'position_seconds' => 'integer',
            'duration_seconds' => 'integer',
            'percent' => 'integer',
            'completed' => 'boolean',
            'last_watched_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /** "12:30", from position_seconds. */
    public function positionLabel(): string
    {
        return self::hms($this->position_seconds);
    }

    public function durationLabel(): string
    {
        return self::hms($this->duration_seconds ?? 0);
    }

    /**
     * Seconds as m:ss or h:mm:ss, the way the player and Continue Watching cards show it.
     */
    public static function hms(?int $seconds): string
    {
        $seconds = max(0, (int) $seconds);
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return $h > 0
            ? sprintf('%d:%02d:%02d', $h, $m, $s)
            : sprintf('%d:%02d', $m, $s);
    }
}
