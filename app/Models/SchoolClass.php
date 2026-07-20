<?php

namespace App\Models;

use Database\Factories\SchoolClassFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A class within a school (e.g. "Tahun 6 Bestari"). Belongs to a school and a Tahun; carries the
 * single source of truth for the homeroom relationship in homeroom_teacher_id.
 */
class SchoolClass extends Model
{
    /** @use HasFactory<SchoolClassFactory> */
    use HasFactory;

    protected $table = 'school_classes';

    protected $fillable = ['school_id', 'grade_id', 'name', 'homeroom_teacher_id', 'is_active'];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'grade_id' => 'integer',
            'homeroom_teacher_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /** The teacher who is homeroom teacher of this class (at most one). */
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    /** Students whose class this is. */
    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'school_class_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** "Tahun 6 Bestari" — the grade name plus the class name. */
    public function label(): string
    {
        $grade = $this->relationLoaded('grade') ? $this->grade : $this->grade()->first();

        return trim(($grade?->name ? $grade->name.' ' : '').$this->name);
    }
}
