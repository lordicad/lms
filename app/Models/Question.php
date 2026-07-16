<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    public const TYPE_SINGLE = 'single';      // radio

    public const TYPE_MULTIPLE = 'multiple';  // checkbox

    protected $fillable = ['quiz_id', 'question_text', 'question_type', 'points', 'sort_order'];

    protected function casts(): array
    {
        return [
            'quiz_id' => 'integer',
            'points' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    public function isMultiple(): bool
    {
        return $this->question_type === self::TYPE_MULTIPLE;
    }

    /**
     * @return array<int, int> ids of the correct options
     */
    public function correctOptionIds(): array
    {
        return $this->options
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Grading. Single choice: the one right option. Multiple choice: all-or-nothing, the
     * selected set has to match the correct set exactly (see plan 7.8; partial credit is
     * deliberately out of scope).
     *
     * @param  array<int, int>  $selectedIds
     */
    public function isAnswerCorrect(array $selectedIds): bool
    {
        $selected = collect($selectedIds)->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
        $correct = $this->correctOptionIds();

        if ($selected === [] || $correct === []) {
            return false;
        }

        return $selected === $correct;
    }
}
