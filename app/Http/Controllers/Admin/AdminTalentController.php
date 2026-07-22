<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use App\Services\TalentService;
use App\Support\ContentFilter;
use App\Support\SchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * MOE oversight of the teaching cohort. Read-only except for one switch: an admin may deactivate
 * a teacher's sign-in. Nothing here edits or removes content — deleting a teacher would cascade
 * through their lessons, materials and quizzes and take students' history with it, so the action
 * deliberately does not exist.
 *
 * Two sections filter independently, so their query strings are namespaced: `subjek`/`tahun` drive
 * the teacher list, `p_subjek`/`p_tahun` the contributor board. Each form re-posts the other's
 * values so filtering one never silently resets the other.
 */
class AdminTalentController extends Controller
{
    public function index(Request $request): View
    {
        $subjectSlug = $request->string('subjek')->toString() ?: null;
        $gradeLevel = $request->integer('tahun') ?: null;
        $contribSubject = $request->string('p_subjek')->toString() ?: null;
        $contribGrade = $request->integer('p_tahun') ?: null;

        // A Subjek only survives once it is offered in the chosen Tahun, so the filter never keeps a
        // pair the Subjek dropdown no longer shows (e.g. after switching to a Year without it).
        $subjectSlug = $this->offeredSubject($subjectSlug, $gradeLevel);
        $contribSubject = $this->offeredSubject($contribSubject, $contribGrade);

        $teachers = $this->teachers($subjectSlug, $gradeLevel);

        return view('admin.bakat', [
            // Guru
            'teachers' => $teachers,
            'subjectsByTeacher' => $this->subjectsByTeacher($teachers->pluck('id')->all()),
            'totalTeachers' => SchoolScope::users(User::where('role', User::ROLE_TEACHER))->count(),
            'activeCount' => SchoolScope::users(User::where('role', User::ROLE_TEACHER))->where('is_active', true)->count(),
            'inactiveCount' => SchoolScope::users(User::where('role', User::ROLE_TEACHER))->where('is_active', false)->count(),
            'subjectSlug' => $subjectSlug,
            'gradeLevel' => $gradeLevel,

            // Penyumbang teratas
            'contributors' => $this->contributors($contribSubject, $contribGrade),
            'contribSubject' => $contribSubject,
            'contribGrade' => $contribGrade,

            // Kandungan terbaik (library-wide: it answers "what is working best", not
            // "what is working best in Tahun 3", so it deliberately ignores both filters)
            'topVideo' => SchoolScope::content(Lesson::published())->with('chapter.subject')->orderByDesc('views_count')->first(),
            'topMaterial' => SchoolScope::content(Material::query())->with('chapter.subject')->orderByDesc('download_count')->first(),
            'topQuiz' => $this->topQuiz(),

            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
        ]);
    }

    /**
     * Flip a teacher's sign-in on or off. Their published content is untouched either way.
     */
    public function toggleStatus(Request $request, User $teacher): RedirectResponse
    {
        abort_unless($teacher->isTeacher(), 404);

        // A teacher at another school is not this admin's to see or change.
        abort_unless(SchoolScope::allows($teacher->school_id), 404);

        $teacher->update(['is_active' => ! $teacher->is_active]);

        return back()->with('status', $teacher->is_active
            ? __('Akaun :name telah diaktifkan.', ['name' => $teacher->name])
            : __('Akaun :name telah dinyahaktifkan. Kandungan mereka kekal untuk murid.', ['name' => $teacher->name]));
    }

    /**
     * Keep a Subjek slug only when it is actually offered in the given Tahun; drop it otherwise.
     * A null slug or null level passes through unchanged (an unfiltered dimension).
     */
    private function offeredSubject(?string $subjectSlug, ?int $gradeLevel): ?string
    {
        if (! $subjectSlug || ! $gradeLevel) {
            return $subjectSlug;
        }

        $grade = Grade::where('level', $gradeLevel)->first();
        $subject = Subject::where('slug', $subjectSlug)->first();

        if ($grade && $subject && ! ContentFilter::isOffered($subject->id, $grade->id)) {
            return null;
        }

        return $subjectSlug;
    }

    // --- Guru --------------------------------------------------------------------------

    /**
     * Teachers, narrowed to those holding content in the chosen Subjek/Tahun. A teacher belongs to
     * no subject of their own — they are placed by what they have posted — so the filter reaches
     * through all three content types, and the counts are scoped the same way to stay consistent
     * with it.
     */
    private function teachers(?string $subjectSlug, ?int $gradeLevel)
    {
        $scope = fn (Builder $query): Builder => $query
            ->when($subjectSlug, fn (Builder $q) => $q->whereHas(
                'chapter.subject',
                fn (Builder $s) => $s->where('slug', $subjectSlug),
            ))
            ->when($gradeLevel, fn (Builder $q) => $q->whereHas(
                'chapter.grade',
                fn (Builder $g) => $g->where('level', $gradeLevel),
            ));

        return SchoolScope::users(User::where('role', User::ROLE_TEACHER))
            ->when($subjectSlug || $gradeLevel, fn (Builder $q) => $q->where(
                fn (Builder $sub) => $sub
                    ->whereHas('lessons', $scope)
                    ->orWhereHas('materials', $scope)
                    ->orWhereHas('quizzes', $scope),
            ))
            ->withCount([
                'lessons as video_count' => $scope,
                'materials as material_count' => $scope,
                'quizzes as quiz_count' => $scope,
            ])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * Subject names per teacher, unioned across the three content types, so the table can say where
     * a teacher actually works. One query rather than eager-loading every lesson of every teacher
     * just to read its subject.
     *
     * @param  array<int, int>  $teacherIds
     * @return Collection<int, Collection<int, string>>
     */
    private function subjectsByTeacher(array $teacherIds): Collection
    {
        if ($teacherIds === []) {
            return collect();
        }

        $of = fn (string $table) => DB::table($table)
            ->join('chapters', 'chapters.id', '=', "{$table}.chapter_id")
            ->whereIn("{$table}.teacher_id", $teacherIds)
            ->select("{$table}.teacher_id", 'chapters.subject_id');

        // The subjects on the teacher's own profile count too, not just the ones inferred from what
        // they have posted. Without this a teacher who is assigned a subject but has not uploaded
        // anything yet reads as teaching nothing at all.
        $assigned = DB::table('subject_teacher')
            ->whereIn('user_id', $teacherIds)
            ->select('user_id as teacher_id', 'subject_id');

        return $of('lessons')
            ->union($of('materials'))
            ->union($of('quizzes'))
            ->union($assigned)
            ->get()
            ->groupBy('teacher_id')
            ->map(fn ($rows) => $rows->pluck('subject_id')->unique()->values());
    }

    // --- Penyumbang teratas ------------------------------------------------------------

    /**
     * The ten biggest contributors: how much published content they have put in, and what it drew.
     * Ranked on content because that is what "contributor" means here; views break ties, so a
     * teacher with the same output but more reach sits higher.
     */
    private function contributors(?string $subjectSlug, ?int $gradeLevel): Collection
    {
        $scope = fn (Builder $query): Builder => $query
            ->when($subjectSlug, fn (Builder $q) => $q->whereHas(
                'chapter.subject',
                fn (Builder $s) => $s->where('slug', $subjectSlug),
            ))
            ->when($gradeLevel, fn (Builder $q) => $q->whereHas(
                'chapter.grade',
                fn (Builder $g) => $g->where('level', $gradeLevel),
            ));

        $published = fn (Builder $query): Builder => $scope($query->where('is_published', true));

        $rows = SchoolScope::users(User::where('role', User::ROLE_TEACHER))
            ->withCount([
                'lessons as published_videos' => $published,
                'materials as published_materials' => $scope,
                'quizzes as published_quizzes' => $published,
            ])
            ->withSum(['lessons as views_sum' => $published], 'views_count')
            ->withSum(['lessons as favourites_sum' => $published], 'favourites_count')
            ->get()
            ->map(function (User $teacher) {
                $teacher->published_content = $teacher->published_videos
                    + $teacher->published_materials
                    + $teacher->published_quizzes;

                return $teacher;
            })
            ->filter(fn (User $teacher) => $teacher->published_content > 0)
            ->sortByDesc(fn (User $teacher) => [$teacher->published_content, (int) $teacher->views_sum])
            ->take(10)
            ->values();

        return $rows;
    }

    // --- Kandungan terbaik -------------------------------------------------------------

    private function topQuiz(): ?Quiz
    {
        return SchoolScope::content(Quiz::query())->with('chapter.subject')
            ->withCount([
                'attempts as attempt_total' => fn (Builder $q) => $q->completed(),
                'attempts as pass_total' => fn (Builder $q) => $q->completed()->passed(),
            ])
            ->orderByDesc('attempt_total')
            ->having('attempt_total', '>', 0)
            ->first();
    }

    // --- Per-teacher talent detail + export (unchanged) ---------------------------------

    public function show(User $teacher, TalentService $talent): View
    {
        abort_unless($teacher->isTeacher(), 404);

        // A teacher at another school is not this admin's to see or change.
        abort_unless(SchoolScope::allows($teacher->school_id), 404);

        return view('admin.bakat-show', [
            'result' => $talent->forTeacher($teacher),
        ]);
    }

    /** A plain streamed CSV — no server-side spreadsheet libraries on shared hosting. */
    public function export(TalentService $talent): StreamedResponse
    {
        $cohort = $talent->cohort();

        return response()->streamDownload(function () use ($cohort) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Guru', 'Emel', 'Skor Bakat', 'Engagement', 'Quality (%)', 'Breadth (bab)',
                'Outcome', 'Murid terlibat', 'Channel disambung', 'Data mencukupi',
            ]);

            foreach ($cohort as $row) {
                fputcsv($out, [
                    $row->teacher->name,
                    $row->teacher->email,
                    $row->headline ?? '',
                    round($row->raw['engagement']),
                    round($row->raw['quality'] * 100, 1),
                    $row->raw['breadth'],
                    $row->raw['outcome'] ?? '',
                    $row->engaged_students,
                    $row->channels,
                    $row->sufficient ? 'Ya' : 'Tidak',
                ]);
            }

            fclose($out);
        }, 'skor-bakat-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
