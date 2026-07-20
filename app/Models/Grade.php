<?php

namespace App\Models;

use Database\Factories\GradeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Grade extends Model
{
    /** @use HasFactory<GradeFactory> */
    use HasFactory;

    protected $fillable = ['name', 'level'];

    protected function casts(): array
    {
        return ['level' => 'integer'];
    }

    /**
     * Grades are addressed by their level (1..6) in URLs, not their id.
     */
    public function getRouteKeyName(): string
    {
        return 'level';
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_STUDENT);
    }

    /**
     * The subjects offered in this Tahun.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class);
    }

    /**
     * Subjects offered in this Tahun, each carrying its published-lesson count for this grade,
     * grouped by category in official order. Drives the student browse page.
     *
     * @return Collection<string, Collection<int, Subject>>
     */
    public function subjectsByCategory(): Collection
    {
        return $this->subjects()
            ->orderBy('sort_order')
            ->withCount(['lessons as lessons_count' => fn ($query) => $query
                ->where('lessons.is_published', true)
                ->where('chapters.grade_id', $this->id)])
            ->get()
            ->groupBy('category');
    }
}
