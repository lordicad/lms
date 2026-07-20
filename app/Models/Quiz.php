<?php

namespace App\Models;

use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory;

    public const TYPE_FILE = 'file';

    public const TYPE_INTERACTIVE = 'interactive';

    protected $table = 'quizzes';

    protected $fillable = [
        'chapter_id',
        'teacher_id',
        'title',
        'description',
        'type',
        'file_path',
        'original_name',
        'duration_minutes',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'chapter_id' => 'integer',
            'teacher_id' => 'integer',
            'is_published' => 'boolean',
            'duration_minutes' => 'integer',
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

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function completedAttempts(): HasMany
    {
        return $this->attempts()->whereNotNull('completed_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function isInteractive(): bool
    {
        return $this->type === self::TYPE_INTERACTIVE;
    }

    public function isFile(): bool
    {
        return $this->type === self::TYPE_FILE;
    }

    public function maxScore(): int
    {
        return (int) $this->questions()->sum('points');
    }

    public function hasAttempts(): bool
    {
        return $this->attempts()->exists();
    }

    /**
     * The attempt that counts for this student's ranking, if they have finished one.
     */
    public function rankedAttemptFor(User $student): ?QuizAttempt
    {
        return $this->attempts()
            ->where('student_id', $student->id)
            ->where('counts_for_ranking', true)
            ->first();
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? Storage::disk('uploads')->url($this->file_path) : null;
    }

    public function deleteFile(): void
    {
        if ($this->file_path) {
            Storage::disk('uploads')->delete($this->file_path);
        }
    }
}
