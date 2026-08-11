<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A cached AI explanation for one (question, locale, chosen-wrong-answer) combination. See the
 * migration for why the cache key excludes the student.
 */
class AnswerExplanation extends Model
{
    protected $fillable = ['question_id', 'locale', 'answer_key', 'explanation'];

    protected function casts(): array
    {
        return [
            'question_id' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * The cache key for a set of selected option ids: order-independent, so the same picks always
     * map to the same row.
     *
     * @param  array<int, int|string>  $selectedOptionIds
     */
    public static function keyFor(array $selectedOptionIds): string
    {
        $ids = collect($selectedOptionIds)->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        return sha1(implode(',', $ids));
    }
}
