<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A YouTube channel a teacher has proven they own, via a one-time Google OAuth read of
 * `channels.list?mine=true`. We store only the public channel identity — never OAuth tokens.
 * A channel_id is unique across the table, so two teachers can never both claim the same channel.
 */
class YoutubeChannel extends Model
{
    protected $fillable = [
        'teacher_id',
        'channel_id',
        'title',
        'thumbnail_url',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'teacher_id' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** Lessons whose stored channelId snapshot matches this verified channel. */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'youtube_channel_id', 'channel_id');
    }
}
