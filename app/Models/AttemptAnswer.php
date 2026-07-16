<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptAnswer extends Model
{
    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'selected_option_ids',
        'is_correct',
        'points_awarded',
    ];

    protected function casts(): array
    {
        return [
            'quiz_attempt_id' => 'integer',
            'question_id' => 'integer',
            'selected_option_ids' => 'array',
            'is_correct' => 'boolean',
            'points_awarded' => 'integer',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function selected(int $optionId): bool
    {
        return in_array($optionId, array_map('intval', $this->selected_option_ids ?? []), true);
    }
}
