<?php

namespace App\Support;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The shared Year -> Subject -> Chapter filter, resolved once on the server.
 *
 * Every teacher/student/admin list that narrows content by Tahun + Subjek (+ Bab) resolves its
 * filter through this class so the dependency rules live in exactly one place. The query-string
 * contract is stable and backward compatible: `tahun` = grade level, `subjek` = subject slug,
 * `bab` = chapter id.
 *
 * The server is the source of truth. A Subject that is not offered in the chosen Year, or a Chapter
 * that does not belong to the chosen Year+Subject, is dropped to a safe empty selection rather than
 * being trusted — client-side dependent dropdowns are only a convenience.
 */
class ContentFilter
{
    public function __construct(
        public readonly ?Grade $grade = null,
        public readonly ?Subject $subject = null,
        public readonly ?Chapter $chapter = null,
    ) {}

    public static function fromRequest(Request $request, string $chapterParam = 'bab'): self
    {
        $grade = $request->filled('tahun')
            ? Grade::where('level', $request->integer('tahun'))->first()
            : null;

        $subject = $request->filled('subjek')
            ? Subject::where('slug', $request->string('subjek')->toString())->first()
            : null;

        // A Subject is only valid once it is offered in the chosen Year. Reject a tampered pair.
        if ($grade && $subject && ! self::isOffered($subject->id, $grade->id)) {
            $subject = null;
        }

        $chapter = null;
        if ($grade && $subject && $request->filled($chapterParam)) {
            $chapter = Chapter::where('id', $request->integer($chapterParam))
                ->where('subject_id', $subject->id)
                ->where('grade_id', $grade->id)
                ->first();
        }

        return new self($grade, $subject, $chapter);
    }

    /** Build a filter for a specific Year, keeping any valid Subject/Chapter from the request. */
    public static function forGrade(Request $request, ?Grade $grade, string $chapterParam = 'bab'): self
    {
        $subject = $request->filled('subjek')
            ? Subject::where('slug', $request->string('subjek')->toString())->first()
            : null;

        if ($grade && $subject && ! self::isOffered($subject->id, $grade->id)) {
            $subject = null;
        }

        $chapter = null;
        if ($grade && $subject && $request->filled($chapterParam)) {
            $chapter = Chapter::where('id', $request->integer($chapterParam))
                ->where('subject_id', $subject->id)
                ->where('grade_id', $grade->id)
                ->first();
        }

        return new self($grade, $subject, $chapter);
    }

    public static function isOffered(int $subjectId, int $gradeId): bool
    {
        return DB::table('grade_subject')
            ->where('subject_id', $subjectId)
            ->where('grade_id', $gradeId)
            ->exists();
    }

    /**
     * Constrain a query (or relation) whose model has a `chapter` relation (Lesson, Material, Quiz)
     * by the resolved Year / Subject / Chapter. Uses whereHas so callers keep their own eager loads.
     *
     * @param  Builder|Relation  $query
     * @return Builder|Relation
     */
    public function apply($query, string $relation = 'chapter')
    {
        return $query
            ->when($this->grade, fn ($q) => $q->whereHas(
                $relation, fn (Builder $c) => $c->where('grade_id', $this->grade->id)
            ))
            ->when($this->subject, fn ($q) => $q->whereHas(
                $relation, fn (Builder $c) => $c->where('subject_id', $this->subject->id)
            ))
            ->when($this->chapter, fn ($q) => $q->where('chapter_id', $this->chapter->id));
    }

    /** Subjects offered in the selected Year, or all subjects when no Year is chosen. */
    public function availableSubjects(Collection $allSubjects): Collection
    {
        if (! $this->grade) {
            return $allSubjects;
        }

        $map = Subject::availabilityMap();

        return $allSubjects
            ->filter(fn (Subject $s) => in_array($this->grade->level, $map[$s->id] ?? [], true))
            ->values();
    }

    /** Active chapters for the selected Year+Subject, ordered; empty when either is missing. */
    public function chaptersForPair(): Collection
    {
        if (! $this->grade || ! $this->subject) {
            return collect();
        }

        return Chapter::where('subject_id', $this->subject->id)
            ->where('grade_id', $this->grade->id)
            ->active()
            ->ordered()
            ->get();
    }

    /** The query-string values to preserve on pagination/sort links. */
    public function queryParams(string $chapterParam = 'bab'): array
    {
        return array_filter([
            'tahun' => $this->grade?->level,
            'subjek' => $this->subject?->slug,
            $chapterParam => $this->chapter?->id,
        ], fn ($value) => $value !== null);
    }

    public function isActive(): bool
    {
        return $this->grade || $this->subject || $this->chapter;
    }
}
