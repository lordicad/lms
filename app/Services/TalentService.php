<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\User;
use App\Support\SchoolScope;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The teacher talent signal — a "look closer" flag for MOE, NOT a verdict or an automatic reward.
 *
 * Computed only from a teacher's own lessons (uploads + verified-own YouTube; counts_for_talent),
 * using IN-SYSTEM engagement only — never YouTube's public view count. Four transparent sub-scores
 * plus one normalised headline, so both the teacher and MOE can see *why*.
 *
 *   engagement = Σ distinct-student reach  +  favourite_weight × Σ (per-student-capped favourites)
 *   quality    = mean(favourites / reach) over lessons with reach ≥ floor   (a rate, not a raw count)
 *   breadth    = distinct chapters with a counted lesson
 *   outcome    = mean quiz accuracy of a lesson's watchers minus the chapter baseline (null if thin)
 *
 * Anti-gaming: only role=student engagement counts (excludes the teacher and all staff); test/
 * autopilot usernames are excluded; one student's favourites are capped; the quality rate has a
 * minimum-reach floor; below a minimum of engaged students a teacher is "data belum mencukupi"
 * (never scored or ranked). All thresholds live in config/talent.php.
 */
class TalentService
{
    /** Every teacher, scored and normalised, sorted by headline desc. Powers the admin dashboard. */
    public function cohort(): Collection
    {
        // Scoped to the signed-in admin's school, so the export carries their own cohort and each
        // teacher is normalised against their peers rather than against every school at once.
        $raws = SchoolScope::users(User::where('role', User::ROLE_TEACHER))
            ->orderBy('id')
            ->get()
            ->map(fn (User $teacher) => $this->rawFor($teacher));

        return $this->normalise($raws);
    }

    /** One teacher's full breakdown, normalised against the cohort so their standing is consistent. */
    public function forTeacher(User $teacher): object
    {
        return $this->cohort()->firstWhere(fn ($row) => $row->teacher->id === $teacher->id)
            ?? $this->normalise(collect([$this->rawFor($teacher)]))->first();
    }

    // --- Raw per-teacher sub-scores -----------------------------------------------------

    private function rawFor(User $teacher): object
    {
        $lessons = $teacher->lessons()->countsForTalent()->with('chapter.subject', 'chapter.grade')->get();

        $row = (object) [
            'teacher' => $teacher,
            'raw' => ['engagement' => 0.0, 'quality' => 0.0, 'breadth' => 0, 'outcome' => null],
            'norm' => ['engagement' => 0.0, 'quality' => 0.0, 'breadth' => 0.0, 'outcome' => null],
            'headline' => null,
            'engaged_students' => 0,
            'sufficient' => false,
            'channels' => $teacher->youtubeChannels()->count(),
            'lessons' => collect(),
        ];

        if ($lessons->isEmpty()) {
            return $row;
        }

        $lessonIds = $lessons->pluck('id')->all();
        $cap = (int) config('talent.per_student_favourite_cap');
        $favWeight = (float) config('talent.favourite_weight');
        $qualityFloor = (int) config('talent.quality_min_reach');

        $reach = $this->distinctByLesson('lesson_views', $lessonIds, $teacher);
        $completion = $this->distinctByLesson('lesson_progress', $lessonIds, $teacher, fn (Builder $q) => $q->where('lesson_progress.completed', true));
        $favourites = $this->countByLesson('favourites', $lessonIds, $teacher);

        // Per-student capped favourites feed engagement, so one account can't inflate a teacher.
        $cappedFavSum = $this->engagementQuery('favourites', $lessonIds, $teacher)
            ->select('favourites.student_id', DB::raw('count(*) as c'))
            ->groupBy('favourites.student_id')
            ->pluck('c', 'favourites.student_id')
            ->sum(fn ($c) => min((int) $c, $cap));

        $sumReach = array_sum($reach->all());
        $engagement = $sumReach + $favWeight * $cappedFavSum;

        // quality = mean favourite-per-viewer rate, only over lessons that clear the reach floor.
        $rates = [];
        foreach ($lessonIds as $id) {
            $r = (int) ($reach[$id] ?? 0);
            if ($r >= $qualityFloor) {
                $rates[] = (int) ($favourites[$id] ?? 0) / $r;
            }
        }
        $quality = $rates === [] ? 0.0 : array_sum($rates) / count($rates);

        $breadth = $lessons->pluck('chapter_id')->unique()->count();
        $engaged = $this->distinctEngagedStudents($lessonIds, $teacher);

        $row->raw = [
            'engagement' => (float) $engagement,
            'quality' => (float) $quality,
            'breadth' => (int) $breadth,
            'outcome' => $this->outcome($teacher, $lessons, $lessonIds),
        ];
        $row->engaged_students = $engaged;
        $row->sufficient = $engaged >= (int) config('talent.min_engaged_students');
        $row->lessons = $lessons->map(fn (Lesson $lesson) => (object) [
            'lesson' => $lesson,
            'reach' => (int) ($reach[$lesson->id] ?? 0),
            'favourites' => (int) ($favourites[$lesson->id] ?? 0),
            'completion' => (int) ($completion[$lesson->id] ?? 0),
        ]);

        return $row;
    }

    /**
     * The learning-outcome anchor: how much better a lesson's watchers do on that chapter's
     * quizzes than the chapter baseline. Returns null when the quiz data is too thin to trust.
     */
    private function outcome(User $teacher, Collection $lessons, array $lessonIds): ?float
    {
        $chapterIds = $lessons->pluck('chapter_id')->unique()->values()->all();
        $quizIds = DB::table('quizzes')->whereIn('chapter_id', $chapterIds)->pluck('id')->all();
        if ($quizIds === []) {
            return null;
        }

        $watcherIds = $this->engagementQuery('lesson_views', $lessonIds, $teacher)
            ->distinct()->pluck('lesson_views.student_id')->all();
        if ($watcherIds === []) {
            return null;
        }

        $attempts = fn () => DB::table('quiz_attempts')
            ->whereIn('quiz_id', $quizIds)
            ->where('counts_for_ranking', true)
            ->whereNotNull('completed_at')
            ->where('question_count', '>', 0);

        $watcher = $attempts()->whereIn('student_id', $watcherIds)
            ->selectRaw('avg(correct_count / question_count * 100) as acc, count(*) as n')->first();

        // Too few watcher-attempts to be meaningful → omit gracefully.
        if (! $watcher || (int) $watcher->n < 5 || $watcher->acc === null) {
            return null;
        }

        $baseline = $attempts()->selectRaw('avg(correct_count / question_count * 100) as acc')->first();
        if (! $baseline || $baseline->acc === null) {
            return null;
        }

        return round((float) $watcher->acc - (float) $baseline->acc, 1);
    }

    // --- Normalisation + headline -------------------------------------------------------

    private function normalise(Collection $raws): Collection
    {
        $maxEngagement = max(1e-9, (float) ($raws->max(fn ($r) => $r->raw['engagement']) ?? 0));
        $maxBreadth = max(1, (int) ($raws->max(fn ($r) => $r->raw['breadth']) ?? 0));

        $uplifts = $raws->map(fn ($r) => $r->raw['outcome'])->filter(fn ($v) => $v !== null);
        $minUp = $uplifts->min();
        $maxUp = $uplifts->max();

        $weights = config('talent.weights');

        return $raws->map(function ($row) use ($maxEngagement, $maxBreadth, $minUp, $maxUp, $weights) {
            $normOutcome = null;
            if ($row->raw['outcome'] !== null) {
                $normOutcome = ($maxUp !== null && $maxUp > $minUp)
                    ? ($row->raw['outcome'] - $minUp) / ($maxUp - $minUp)
                    : 0.5; // only one teacher has outcome data → neutral rather than a hard 0 or 1
            }

            $row->norm = [
                'engagement' => min(1.0, $row->raw['engagement'] / $maxEngagement),
                'quality' => min(1.0, max(0.0, $row->raw['quality'])),
                'breadth' => min(1.0, $row->raw['breadth'] / $maxBreadth),
                'outcome' => $normOutcome,
            ];

            // Weighted blend over the components that are present (outcome omitted when null),
            // re-normalised so a missing outcome doesn't silently drag the headline down.
            $present = ['engagement', 'quality', 'breadth'];
            if ($normOutcome !== null) {
                $present[] = 'outcome';
            }

            $weightSum = 0.0;
            $acc = 0.0;
            foreach ($present as $key) {
                $acc += $weights[$key] * $row->norm[$key];
                $weightSum += $weights[$key];
            }

            $row->headline = $row->sufficient ? round(100 * $acc / max($weightSum, 1e-9), 1) : null;

            return $row;
        })->sortByDesc(fn ($r) => $r->headline ?? -1)->values();
    }

    // --- Engagement queries (student-only, test/self excluded) --------------------------

    /**
     * Base query over an engagement table joined to users, restricted to real student engagement:
     * role=student, not the teacher themselves, and not any excluded (test/autopilot) username.
     */
    private function engagementQuery(string $table, array $lessonIds, User $teacher): Builder
    {
        $query = DB::table($table)
            ->join('users', 'users.id', '=', "{$table}.student_id")
            ->whereIn("{$table}.lesson_id", $lessonIds)
            ->where('users.role', User::ROLE_STUDENT)
            ->where('users.id', '!=', $teacher->id);

        foreach ((array) config('talent.excluded_username_patterns') as $pattern) {
            $query->where('users.username', 'not like', $pattern);
        }

        return $query;
    }

    /** @return Collection<int,int> lesson_id => distinct engaged students */
    private function distinctByLesson(string $table, array $lessonIds, User $teacher, ?callable $extra = null): Collection
    {
        $query = $this->engagementQuery($table, $lessonIds, $teacher);

        if ($extra) {
            $extra($query);
        }

        return $query->select("{$table}.lesson_id", DB::raw("count(distinct {$table}.student_id) as n"))
            ->groupBy("{$table}.lesson_id")
            ->pluck('n', "{$table}.lesson_id");
    }

    /** @return Collection<int,int> lesson_id => row count */
    private function countByLesson(string $table, array $lessonIds, User $teacher): Collection
    {
        return $this->engagementQuery($table, $lessonIds, $teacher)
            ->select("{$table}.lesson_id", DB::raw('count(*) as n'))
            ->groupBy("{$table}.lesson_id")
            ->pluck('n', "{$table}.lesson_id");
    }

    private function distinctEngagedStudents(array $lessonIds, User $teacher): int
    {
        $viewers = $this->engagementQuery('lesson_views', $lessonIds, $teacher)
            ->distinct()->pluck('lesson_views.student_id');
        $favouriters = $this->engagementQuery('favourites', $lessonIds, $teacher)
            ->distinct()->pluck('favourites.student_id');

        return $viewers->merge($favouriters)->unique()->count();
    }
}
