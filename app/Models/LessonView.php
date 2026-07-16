<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonView extends Model
{
    public const UPDATED_AT = null;   // only created_at, one row per student per lesson

    protected $fillable = ['lesson_id', 'student_id'];

    protected function casts(): array
    {
        return [
            'lesson_id' => 'integer',
            'student_id' => 'integer',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
