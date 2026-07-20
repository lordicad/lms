<?php

namespace App\Models;

use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory;

    /**
     * What the admin oversight page reads as a pass.
     *
     * The ministry has set no pass mark and the app deliberately has none: a student is never
     * told they failed. This is the reporting threshold only, and it matches the celebration
     * screen's 80% on purpose, so "well done" to a child and "pass" to MOE mean the same thing.
     * If a real pass mark ever arrives, it belongs in config/lms.php — and it should not silently
     * move what children are congratulated for.
     */
    public const PASS_AT = 80;

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
        return $this->percentage() >= self::PASS_AT;
    }

    public function isPassed(): bool
    {
        return $this->percentage() >= self::PASS_AT;
    }

    /**
     * Attempts at or above the pass mark. Guards max_score, because a quiz whose questions were
     * all deleted leaves attempts scoring 0/0 — dividing by that would error, and they are not
     * passes.
     */
    public function scopePassed(Builder $query): Builder
    {
        return $query->where('max_score', '>', 0)
            ->whereRaw('(score / max_score) * 100 >= ?', [self::PASS_AT]);
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
