<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chapter extends Model
{
    protected $fillable = ['subject_id', 'grade_id', 'number', 'title', 'description', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return [
            'subject_id' => 'integer',
            'grade_id' => 'integer',
            'created_by' => 'integer',
            'number' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function publishedLessons(): HasMany
    {
        return $this->lessons()->where('is_published', true);
    }

    public function publishedQuizzes(): HasMany
    {
        return $this->quizzes()->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('number');
    }

    /**
     * Chapters still part of the curriculum. Content only ever goes into active chapters;
     * a chapter is deactivated (never deleted) when its (subject, Tahun) leaves the syllabus.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * A chapter is shared taxonomy, so it may only be removed while nothing lives in it.
     */
    public function isEmpty(): bool
    {
        return ! $this->lessons()->exists()
            && ! $this->materials()->exists()
            && ! $this->quizzes()->exists();
    }

    public function label(): string
    {
        return "Bab {$this->number}: {$this->title}";
    }
}
