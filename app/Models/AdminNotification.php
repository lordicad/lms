<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A notification in the admin bell, scoped to a school so every admin of that school sees it. The
 * teacher equivalent (TeacherNotification) is per-user; an admin's work is shared across the
 * school, so these are keyed by school instead.
 */
class AdminNotification extends Model
{
    /** A user proved control of their email via OTP and needs an admin to reset their password. */
    public const TYPE_PASSWORD_RESET = 'password_reset_request';

    protected $fillable = ['school_id', 'type', 'actor_name', 'title', 'url', 'read_at'];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'read_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Raise a notification. `$title` carries the denormalised detail (e.g. the username) that the
     * bell's per-type message template renders alongside the actor's name.
     */
    public static function record(?int $schoolId, string $type, string $actorName, string $title, ?string $url = null): void
    {
        static::create([
            'school_id' => $schoolId,
            'type' => $type,
            'actor_name' => $actorName,
            'title' => $title,
            'url' => $url,
        ]);
    }
}
