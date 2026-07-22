<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Support\SchoolScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * MOE oversight of the student body. Read-only throughout.
 *
 * Each Tahun on the podium filters its own subject, so the query strings are namespaced per year
 * (`subjek_3` drives Tahun 3) and every form carries the others through: picking a subject for one
 * year must not quietly reset the other five.
 */
class AdminStudentController extends Controller
{
    public function index(Request $request): View
    {
        $grades = Grade::orderBy('level')->get();
        $gradeLevel = $request->integer('tahun') ?: null;

        // Classes belong to a school and a year, so the list follows both: the admin's own school,
        // and the Tahun on screen. A class from a year that is no longer selected is dropped rather
        // than left filtering invisibly.
        $classes = SchoolClass::query()
            ->when(SchoolScope::currentSchoolId(), fn ($q, $id) => $q->where('school_id', $id))
            ->when($gradeLevel, fn ($q) => $q->whereHas('grade', fn ($g) => $g->where('level', $gradeLevel)))
            ->with('grade')
            ->orderBy('grade_id')
            ->orderBy('name')
            ->get();

        $classId = $request->integer('kelas') ?: null;
        $classId = $classes->contains('id', $classId) ? $classId : null;

        return view('admin.murid', [
            // Counts are deliberately unfiltered — they describe the school, not the table.
            'totalStudents' => SchoolScope::users(User::where('role', User::ROLE_STUDENT))->count(),
            'countsByGrade' => $this->countsByGrade(),

            'students' => $this->students($gradeLevel, $classId),
            'gradeLevel' => $gradeLevel,
            'classes' => $classes,
            'classId' => $classId,

            'podiums' => $this->podiums($grades, $request),

            'grades' => $grades,
            'subjects' => Subject::orderBy('sort_order')->get(),
        ]);
    }

    /**
     * Student headcount per Tahun, keyed by level, so a card can read its own number.
     *
     * @return Collection<int, int>
     */
    private function countsByGrade(): Collection
    {
        return SchoolScope::users(User::query())
            ->where('users.role', User::ROLE_STUDENT)
            ->join('grades', 'grades.id', '=', 'users.grade_id')
            ->groupBy('grades.level')
            ->selectRaw('grades.level as level, count(*) as total')
            ->pluck('total', 'level');
    }

    /**
     * The roster. Passes are counted at the reporting threshold and fails are the remainder, so the
     * two always add up to the attempts column rather than drifting from it.
     */
    private function students(?int $gradeLevel, ?int $classId = null): LengthAwarePaginator
    {
        return SchoolScope::users(User::where('role', User::ROLE_STUDENT))
            ->with('grade', 'schoolClass')
            ->when($gradeLevel, fn (Builder $q) => $q->whereHas(
                'grade',
                fn (Builder $g) => $g->where('level', $gradeLevel),
            ))
            ->when($classId, fn (Builder $q) => $q->where('school_class_id', $classId))
            ->withCount([
                'lessonViews as videos_viewed',
                'attempts as attempts_count' => fn (Builder $q) => $q->completed(),
                'attempts as pass_count' => fn (Builder $q) => $q->completed()->passed(),
            ])
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Top three per Tahun, each year filterable by its own subject.
     *
     * Ranked on effort — videos watched + completed quiz attempts + favourites, equal weight. That
     * is a deliberate choice to reward taking part rather than scoring highest, and it means this
     * board will NOT agree with the /ranking one students see, which is points-based. The formula
     * is printed on the page so the difference is visible rather than mysterious.
     *
     * @return Collection<int, object>
     */
    private function podiums(Collection $grades, Request $request): Collection
    {
        return $grades->map(function (Grade $grade) use ($request) {
            $slug = $request->string("subjek_{$grade->level}")->toString() ?: null;

            // Views, favourites and attempts all hang off a chapter, so one subject clause narrows
            // each of them — it just reaches through a different relation each time.
            $through = fn (string $relation): callable => function (Builder $query) use ($relation, $slug): Builder {
                return $query->when($slug, fn (Builder $q) => $q->whereHas(
                    "{$relation}.chapter.subject",
                    fn (Builder $s) => $s->where('slug', $slug),
                ));
            };

            $students = SchoolScope::users(User::where('role', User::ROLE_STUDENT))
                ->where('grade_id', $grade->id)
                ->withCount([
                    'lessonViews as videos' => $through('lesson'),
                    'favourites as favourites' => $through('lesson'),
                    'attempts as attempts' => function (Builder $query) use ($through): Builder {
                        return $through('quiz')($query->completed());
                    },
                ])
                ->get()
                ->map(function (User $student): User {
                    $student->effort = $student->videos + $student->attempts + $student->favourites;

                    return $student;
                })
                // A student with no activity is not a top student; an empty podium says so honestly.
                ->filter(fn (User $student): bool => $student->effort > 0)
                ->sortByDesc('effort')
                ->take(3)
                ->values();

            return (object) [
                'grade' => $grade,
                'subjectSlug' => $slug,
                'students' => $students,
            ];
        });
    }
}
