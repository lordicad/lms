<?php

namespace App\Models;

use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

    protected $fillable = ['name', 'short_name', 'slug', 'category', 'color', 'icon', 'sort_order'];

    /**
     * Kurikulum Persekolahan 2027 categories, in the official display order used on the
     * browse page and the leaderboard subject picker.
     */
    public const CATEGORIES = ['teras', 'wajib', 'wajib_mbpk', 'tambahan', 'program'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    /**
     * The Tahun (grades) this subject is offered in. Availability is Tahap-dependent, so a
     * subject may exist for Tahun 5–6 only, etc.
     */
    public function grades(): BelongsToMany
    {
        return $this->belongsToMany(Grade::class);
    }

    /** Teachers who teach this subject (many-to-many). */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subject_teacher', 'subject_id', 'user_id');
    }

    /**
     * Every lesson under this subject, across all chapters. Lets a caller count lessons per
     * subject for one Tahun without a correlated subquery per row.
     */
    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Chapter::class);
    }

    public function quizzes(): HasManyThrough
    {
        return $this->hasManyThrough(Quiz::class, Chapter::class);
    }

    /**
     * Subjects offered in a given Tahun. Accepts a Grade or a raw level (1..6).
     */
    public function scopeAvailableFor(Builder $query, Grade|int $level): Builder
    {
        $gradeId = $level instanceof Grade
            ? $level->id
            : Grade::where('level', $level)->value('id');

        return $query->whereHas('grades', fn (Builder $q) => $q->where('grades.id', $gradeId));
    }

    /**
     * The compact label for cards, tabs and options: the short name if the subject has one,
     * otherwise the full name. Names are never translated (decision A1).
     */
    public function displayName(): string
    {
        return $this->short_name ?: $this->name;
    }

    /**
     * The availability map, [subjectId => [level, ...]], for embedding into the dependent
     * Subject → Tahun dropdowns via @json so no extra endpoint is needed.
     *
     * @return array<int, array<int, int>>
     */
    public static function availabilityMap(): array
    {
        return DB::table('grade_subject')
            ->join('grades', 'grades.id', '=', 'grade_subject.grade_id')
            ->orderBy('grades.level')
            ->get(['grade_subject.subject_id', 'grades.level'])
            ->groupBy('subject_id')
            ->map(fn ($rows) => $rows->pluck('level')->map(fn ($level) => (int) $level)->all())
            ->toArray();
    }

    /**
     * Human category heading, translated. Subject names themselves stay untranslated.
     */
    public static function categoryLabel(string $category): string
    {
        return match ($category) {
            'teras' => __('Mata Pelajaran Teras'),
            'wajib' => __('Mata Pelajaran Wajib'),
            'wajib_mbpk' => __('Mata Pelajaran Wajib Untuk MBPK (Inklusif)'),
            'tambahan' => __('Mata Pelajaran Tambahan'),
            'program' => __('Program'),
            default => $category,
        };
    }

    /**
     * The subject accent as an "R G B" triplet, ready for `style="--sc: {{ $subject->rgb }}"`.
     * Every subject-tinted surface in the app reads from that custom property.
     */
    public function getRgbAttribute(): string
    {
        [$r, $g, $b] = sscanf(ltrim($this->color, '#'), '%2x%2x%2x') ?: [15, 118, 110];

        return "{$r} {$g} {$b}";
    }
}
