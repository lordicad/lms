<?php

namespace App\Models;

use Database\Factories\QuestionOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    /** @use HasFactory<QuestionOptionFactory> */
    use HasFactory;

    protected $fillable = ['question_id', 'option_text', 'option_text_translated', 'is_correct', 'sort_order'];

    protected function casts(): array
    {
        return [
            'question_id' => 'integer',
            'is_correct' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * A, B, C, D ... shown to students instead of raw ids.
     */
    public function letter(): string
    {
        return chr(65 + $this->sort_order);
    }

    /**
     * The option text in the reader's language. The source locale lives on the parent question
     * (options share it), so it is passed in to avoid loading the question per option.
     */
    public function localizedText(?string $sourceLocale): string
    {
        if (! $sourceLocale || app()->getLocale() === $sourceLocale) {
            return $this->option_text;
        }

        return $this->option_text_translated ?: $this->option_text;
    }
}
