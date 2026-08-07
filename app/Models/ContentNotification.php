<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A "new content" notification shown to students. Unlike the teacher/admin notifications (one row
 * per recipient), this is a single broadcast row per uploaded item: students in the same school and
 * Tahun (grade) all read the same feed via scopeFor(). Read state is per-student and lives on the
 * user (content_notifications_read_at), so there is no read_at here.
 */
class ContentNotification extends Model
{
    public const TYPE_VIDEO = 'video';

    public const TYPE_MATERIAL = 'material';

    public const TYPE_QUIZ = 'quiz';

    protected $fillable = ['school_id', 'grade_id', 'type', 'content_id', 'actor_name', 'title', 'url'];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'grade_id' => 'integer',
            'content_id' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * The feed for a student: their school's teachers, their own Tahun. Either key missing means an
     * empty feed (a student with no school or no grade sees nothing rather than everything).
     */
    public static function scopeFor(?int $schoolId, ?int $gradeId): Builder
    {
        if (! $schoolId || ! $gradeId) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()->where('school_id', $schoolId)->where('grade_id', $gradeId);
    }

    /**
     * Announce a piece of content to its school + Tahun, the first time it becomes visible to
     * students. Idempotent: a given item is announced once (dedup on type + content_id), so a video
     * whose publish state is toggled off and on again never re-notifies.
     *
     * `$content` is a Lesson, Material or Quiz — each has a `chapter` (→ grade) and a `teacher`
     * (→ school). Anything missing (no chapter/teacher/school/grade) is skipped silently.
     */
    public static function announce(Model $content, string $type): void
    {
        $content->loadMissing('chapter', 'teacher');
        $chapter = $content->chapter;
        $teacher = $content->teacher;

        if (! $chapter || ! $chapter->grade_id || ! $teacher || ! $teacher->school_id) {
            return;
        }

        if (static::where('type', $type)->where('content_id', $content->id)->exists()) {
            return;
        }

        static::create([
            'school_id' => $teacher->school_id,
            'grade_id' => $chapter->grade_id,
            'type' => $type,
            'content_id' => $content->id,
            'actor_name' => $teacher->name,
            'title' => $content->title,
            'url' => route('bab.show', $chapter),
        ]);
    }
}
