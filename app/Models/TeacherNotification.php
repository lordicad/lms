<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherNotification extends Model
{
    public const TYPE_QUIZ = 'quiz_attempt';

    public const TYPE_FAVOURITE = 'favourite';

    public const TYPE_DOWNLOAD = 'download';

    /** @var list<string> */
    protected $fillable = [
        'teacher_id',
        'type',
        'actor_name',
        'title',
        'url',
        'read_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Record a notification for the teacher who owns the content. No-op when the content has
     * no teacher, there is no actor, or the actor is the teacher themselves (a teacher opening
     * their own file should not ping their own bell).
     */
    public static function record(?int $teacherId, ?User $actor, string $type, string $title, ?string $url = null): void
    {
        if (! $teacherId || ! $actor || $actor->id === $teacherId) {
            return;
        }

        static::create([
            'teacher_id' => $teacherId,
            'type' => $type,
            'actor_name' => $actor->name,
            'title' => $title,
            'url' => $url,
        ]);
    }
}
