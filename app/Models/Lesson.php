<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Lesson extends Model
{
    public const SOURCE_UPLOAD = 'upload';

    public const SOURCE_YOUTUBE = 'youtube';

    // Ownership attribution — see the YouTube ownership task. Only 'upload' and 'owned' feed the
    // teacher talent signal; 'reference' (someone else's YouTube video) is watchable but excluded.
    public const OWNERSHIP_UPLOAD = 'upload';

    public const OWNERSHIP_OWNED = 'owned';

    public const OWNERSHIP_REFERENCE = 'reference';

    protected $fillable = [
        'chapter_id',
        'teacher_id',
        'title',
        'description',
        'source',
        'video_path',
        'youtube_id',
        'youtube_channel_id',
        'thumbnail_path',
        'duration_seconds',
        'ownership',
        'counts_for_talent',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'chapter_id' => 'integer',
            'teacher_id' => 'integer',
            'is_published' => 'boolean',
            'counts_for_talent' => 'boolean',
            'views_count' => 'integer',
            'duration_seconds' => 'integer',
            'favourites_count' => 'integer',
        ];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** The verified channel matching this lesson's snapshot channelId, if any (owned lessons). */
    public function youtubeChannel(): BelongsTo
    {
        return $this->belongsTo(YoutubeChannel::class, 'youtube_channel_id', 'channel_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(LessonView::class);
    }

    public function favourites(): HasMany
    {
        return $this->hasMany(Favourite::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * This student's saved watch progress for the lesson, if any.
     */
    public function progressFor(?User $student): ?LessonProgress
    {
        if (! $student) {
            return null;
        }

        return $this->progress()->where('student_id', $student->id)->first();
    }

    public function isFavouritedBy(?User $student): bool
    {
        if (! $student) {
            return false;
        }

        return $this->favourites()->where('student_id', $student->id)->exists();
    }

    /**
     * "12:30" once a duration has been captured on first play; null until then.
     */
    public function durationLabel(): ?string
    {
        return $this->duration_seconds ? LessonProgress::hms($this->duration_seconds) : null;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** Lessons that feed the teacher talent signal: direct uploads and verified-own YouTube. */
    public function scopeCountsForTalent(Builder $query): Builder
    {
        return $query->where('counts_for_talent', true);
    }

    public function isOwned(): bool
    {
        return $this->ownership === self::OWNERSHIP_OWNED;
    }

    public function isReference(): bool
    {
        return $this->ownership === self::OWNERSHIP_REFERENCE;
    }

    /**
     * Eager-loads exactly what a lesson card needs — subject, plus this student's own progress
     * and favourite — so a rail of cards never N+1s. The relations are scoped to the student, so
     * a card reads its own progress/favourite straight off the loaded models.
     */
    public function scopeWithStudentContext(Builder $query, User $student): Builder
    {
        return $query->with([
            'chapter.subject',
            'progress' => fn ($q) => $q->where('student_id', $student->id),
            'favourites' => fn ($q) => $q->where('student_id', $student->id),
        ]);
    }

    public function isUpload(): bool
    {
        return $this->source === self::SOURCE_UPLOAD;
    }

    public function isYoutube(): bool
    {
        return $this->source === self::SOURCE_YOUTUBE;
    }

    /**
     * Direct URL to the uploaded file. Served by the web server, so byte-range requests
     * work and students can scrub through a long recording.
     */
    public function videoUrl(): ?string
    {
        return $this->video_path ? Storage::disk('uploads')->url($this->video_path) : null;
    }

    /**
     * Privacy-preserving embed. The student never leaves the platform.
     */
    public function embedUrl(): ?string
    {
        return $this->youtube_id
            ? "https://www.youtube-nocookie.com/embed/{$this->youtube_id}?rel=0&modestbranding=1"
            : null;
    }

    public function thumbnailUrl(): ?string
    {
        if ($this->thumbnail_path) {
            return Storage::disk('uploads')->url($this->thumbnail_path);
        }

        if ($this->isYoutube()) {
            return "https://i.ytimg.com/vi/{$this->youtube_id}/hqdefault.jpg";
        }

        return null;
    }

    public function watchedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->views()->where('student_id', $user->id)->exists();
    }

    /**
     * Previous and next lesson inside the same Bab, so students can move straight on.
     */
    public function previousInChapter(): ?self
    {
        return static::published()
            ->where('chapter_id', $this->chapter_id)
            ->where('id', '<', $this->id)
            ->orderByDesc('id')
            ->first();
    }

    public function nextInChapter(): ?self
    {
        return static::published()
            ->where('chapter_id', $this->chapter_id)
            ->where('id', '>', $this->id)
            ->orderBy('id')
            ->first();
    }

    /**
     * Removes the video and thumbnail from disk. Called when the lesson is deleted or
     * when the teacher swaps an upload for a YouTube link.
     */
    public function deleteFiles(): void
    {
        $disk = Storage::disk('uploads');

        if ($this->video_path) {
            $disk->delete($this->video_path);
        }

        if ($this->thumbnail_path) {
            $disk->delete($this->thumbnail_path);
        }
    }

    protected static function booted(): void
    {
        // Exactly one video source per lesson, and uploads are always own-work that counts.
        static::saving(function (self $lesson) {
            if ($lesson->source === self::SOURCE_UPLOAD) {
                $lesson->youtube_id = null;
                $lesson->youtube_channel_id = null;
                $lesson->ownership = self::OWNERSHIP_UPLOAD;
                $lesson->counts_for_talent = true;
            } elseif ($lesson->source === self::SOURCE_YOUTUBE) {
                $lesson->video_path = null;
                // ownership / counts_for_talent / youtube_channel_id are set by OwnershipService.
            }
        });
    }
}
