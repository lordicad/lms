<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    protected $fillable = [
        'quiz_id',
        'student_id',
        'score',
        'max_score',
        'correct_count',
        'question_count',
        'counts_for_ranking',
        'started_at',
        'completed_at',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'quiz_id' => 'integer',
            'student_id' => 'integer',
            'counts_for_ranking' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'score' => 'integer',
            'max_score' => 'integer',
            'correct_count' => 'integer',
            'question_count' => 'integer',
            'duration_seconds' => 'integer',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeRanked(Builder $query): Builder
    {
        return $query->where('counts_for_ranking', true);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function percentage(): int
    {
        if ($this->max_score <= 0) {
            return 0;
        }

        return (int) round($this->score / $this->max_score * 100);
    }

    /**
     * Anything at 80% or better gets the celebration screen.
     */
    public function isCelebration(): bool
    {
        return $this->percentage() >= 80;
    }

    /**
     * Retries are practice: they are graded and reviewable but never touch the leaderboard.
     */
    public function isPractice(): bool
    {
        return ! $this->counts_for_ranking;
    }

    public function humanDuration(): string
    {
        $seconds = (int) $this->duration_seconds;

        if ($seconds <= 0) {
            return '-';
        }

        $minutes = intdiv($seconds, 60);
        $rest = $seconds % 60;

        return $minutes > 0 ? "{$minutes} min {$rest} saat" : "{$rest} saat";
    }
}
