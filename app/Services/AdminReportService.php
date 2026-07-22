<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonView;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Support\SchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Every aggregate on the Admin Home page, computed once here so the page and the PDF/DOCX exports
 * read exactly the same numbers. All values are real, server-side aggregates.
 */
class AdminReportService
{
    /** Allow-listed Platform Activity reporting periods. */
    public const PERIODS = ['7d', '30d', '12m'];

    public const PERIOD_LABELS = [
        '7d' => '7 hari lalu',
        '30d' => '30 hari lalu',
        '12m' => '12 bulan lalu',
    ];

    /**
     * All-time platform totals for the four summary cards, each paired with a `*_new` count: how
     * many of that record were created in the last 7 days. The cards show that as a "+N" trend, so
     * it is a real delta over a fixed window, not a projection.
     */
    public function totals(): array
    {
        $since = Carbon::now()->subDays(7);

        return [
            'students' => SchoolScope::users(User::where('role', User::ROLE_STUDENT))->count(),
            'teachers' => SchoolScope::users(User::where('role', User::ROLE_TEACHER))->count(),
            'videos' => SchoolScope::content(Lesson::query())->count(),
            'quizzes' => SchoolScope::content(Quiz::query())->count(),

            'students_new' => SchoolScope::users(User::where('role', User::ROLE_STUDENT))->where('created_at', '>=', $since)->count(),
            'teachers_new' => SchoolScope::users(User::where('role', User::ROLE_TEACHER))->where('created_at', '>=', $since)->count(),
            'videos_new' => SchoolScope::content(Lesson::query())->where('created_at', '>=', $since)->count(),
            'quizzes_new' => SchoolScope::content(Quiz::query())->where('created_at', '>=', $since)->count(),
        ];
    }

    /**
     * Teacher contributors ranked by a transparent metric:
     *   contribution = Videos + Materials + Quizzes created.
     * Tie-break: Videos, then Materials, then Quizzes, then full name, then id — fully deterministic.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function contributors(): Collection
    {
        return SchoolScope::users(User::where('role', User::ROLE_TEACHER))
            ->withCount(['lessons', 'materials', 'quizzes'])
            ->with('school:id,name')
            ->get()
            ->map(fn (User $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'school' => $t->school?->name,
                'videos' => $t->lessons_count,
                'materials' => $t->materials_count,
                'quizzes' => $t->quizzes_count,
                'total' => $t->lessons_count + $t->materials_count + $t->quizzes_count,
            ])
            ->sort(function (array $a, array $b) {
                return [$b['total'], $b['videos'], $b['materials'], $b['quizzes']]
                    <=> [$a['total'], $a['videos'], $a['materials'], $a['quizzes']]
                    ?: strcmp($a['name'], $b['name'])
                    ?: ($a['id'] <=> $b['id']);
            })
            ->values();
    }

    /**
     * Top-performing content with teacher attribution. Ties are broken by lowest id for a stable,
     * documented order.
     *
     * @return array{video: ?array<string, mixed>, material: ?array<string, mixed>, quiz: ?array<string, mixed>}
     */
    public function topContent(): array
    {
        $video = SchoolScope::content(Lesson::query())->with('teacher:id,name')
            ->orderByDesc('views_count')->orderBy('id')->first();

        $material = SchoolScope::content(Material::query())->with('teacher:id,name')
            ->orderByDesc('download_count')->orderBy('id')->first();

        $quiz = SchoolScope::content(Quiz::query())->with('teacher:id,name')
            ->withCount(['completedAttempts as completed_count'])
            ->orderByDesc('completed_count')->orderBy('id')->first();

        return [
            'video' => ($video && $video->views_count > 0) ? [
                'title' => $video->title, 'teacher' => $video->teacher?->name, 'count' => $video->views_count,
            ] : null,
            'material' => ($material && $material->download_count > 0) ? [
                'title' => $material->title, 'teacher' => $material->teacher?->name, 'count' => $material->download_count,
            ] : null,
            'quiz' => ($quiz && $quiz->completed_count > 0) ? [
                'title' => $quiz->title, 'teacher' => $quiz->teacher?->name, 'count' => $quiz->completed_count,
            ] : null,
        ];
    }

    /**
     * Time-series platform activity for a reporting period: video views, completed quiz attempts,
     * passed quiz attempts, and content uploads (videos + materials + quizzes). Zero periods are
     * included so the axis is continuous, and buckets use the application timezone.
     *
     * @return array{labels: array<int, string>, series: array<string, array<int, int>>}
     */
    public function platformActivity(string $period): array
    {
        $monthly = $period === '12m';
        [$keys, $labels] = $this->buckets($period);
        $start = Carbon::parse($keys[0]);   // first bucket (a date, or the first of a month)

        $views = $this->bucketed(LessonView::query(), 'created_at', $keys, $monthly, $start);
        $completed = $this->bucketed(SchoolScope::content(QuizAttempt::query(), 'quiz.teacher')->completed(), 'completed_at', $keys, $monthly, $start);
        $passed = $this->bucketed(SchoolScope::content(QuizAttempt::query(), 'quiz.teacher')->completed()->passed(), 'completed_at', $keys, $monthly, $start);

        $lessons = $this->bucketed(SchoolScope::content(Lesson::query()), 'created_at', $keys, $monthly, $start);
        $materials = $this->bucketed(SchoolScope::content(Material::query()), 'created_at', $keys, $monthly, $start);
        $quizzes = $this->bucketed(SchoolScope::content(Quiz::query()), 'created_at', $keys, $monthly, $start);
        $uploads = array_map(fn ($i) => $lessons[$i] + $materials[$i] + $quizzes[$i], array_keys($keys));

        return [
            'labels' => $labels,
            'series' => [
                'views' => $views,
                'completed' => $completed,
                'passed' => $passed,
                'uploads' => $uploads,
            ],
        ];
    }

    /**
     * The bucket keys and human labels for a period, in the application timezone.
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function buckets(string $period): array
    {
        if ($period === '12m') {
            $start = Carbon::now()->startOfMonth()->subMonths(11);
            $keys = [];
            $labels = [];
            for ($i = 0; $i < 12; $i++) {
                $month = $start->copy()->addMonths($i);
                $keys[] = $month->format('Y-m');
                $labels[] = $month->translatedFormat('M Y');
            }

            return [$keys, $labels];
        }

        $days = $period === '30d' ? 30 : 7;
        $start = Carbon::today()->subDays($days - 1);
        $keys = [];
        $labels = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $keys[] = $day->toDateString();
            $labels[] = $day->translatedFormat('d/m');
        }

        return [$keys, $labels];
    }

    /**
     * Group a query's rows into the given buckets (day or month) and return a zero-filled series
     * aligned to $keys.
     *
     * @param  array<int, string>  $keys
     * @return array<int, int>
     */
    private function bucketed(Builder $query, string $column, array $keys, bool $monthly, Carbon $start): array
    {
        $format = $monthly ? '%Y-%m' : '%Y-%m-%d';

        $counts = $query
            ->where($column, '>=', $start->copy()->startOfDay())
            ->selectRaw("DATE_FORMAT({$column}, ?) as bucket, COUNT(*) as aggregate", [$format])
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket');

        return array_map(fn (string $key) => (int) ($counts[$key] ?? 0), $keys);
    }

    /**
     * Teacher and student accounts registered within the rolling last-7-days window, newest first.
     */
    public function recentRegistrationsQuery(): Builder
    {
        return SchoolScope::users(User::whereIn('role', [User::ROLE_STUDENT, User::ROLE_TEACHER]))
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->with('grade')
            ->latest('created_at');
    }

    /**
     * Real oversight signals the admin can act on — never invented. Only non-empty ones show; if the
     * platform is entirely tidy a single "all clear" item is returned. Shared by the dashboard and
     * the exports so the report matches the page.
     *
     * Each item carries the page that resolves it, so the dashboard can link straight there rather
     * than naming a screen the admin then has to go find. The all-clear item has no destination.
     *
     * @return array<int, array{icon:string,bg:string,fg:string,title:string,desc:string,url:?string}>
     */
    public function pending(): array
    {
        $inactiveTeachers = SchoolScope::users(User::where('role', User::ROLE_TEACHER))->where('is_active', false)->count();
        $draftVideos = SchoolScope::content(Lesson::query())->where('is_published', false)->count();
        $silentTeachers = SchoolScope::users(User::where('role', User::ROLE_TEACHER))
            ->whereDoesntHave('lessons')
            ->whereDoesntHave('materials')
            ->whereDoesntHave('quizzes')
            ->count();

        $items = [];

        if ($inactiveTeachers > 0) {
            $items[] = [
                'icon' => 'userx', 'bg' => '#FDE7E0', 'fg' => '#C24936',
                'title' => trans_choice('{1}:count akaun cikgu dinyahaktifkan|[2,*]:count akaun cikgu dinyahaktifkan', $inactiveTeachers, ['count' => $inactiveTeachers]),
                'desc' => __('Kandungan mereka kekal untuk murid. Semak di halaman Cikgu.'),
                'url' => route('admin.bakat'),
            ];
        }

        if ($draftVideos > 0) {
            $items[] = [
                'icon' => 'video', 'bg' => '#FEF0CE', 'fg' => '#8A6A12',
                'title' => trans_choice('{1}:count video belum diterbitkan|[2,*]:count video belum diterbitkan', $draftVideos, ['count' => $draftVideos]),
                'desc' => __('Draf yang belum kelihatan kepada murid.'),
                'url' => route('admin.kandungan.video'),
            ];
        }

        if ($silentTeachers > 0) {
            $items[] = [
                'icon' => 'teachers', 'bg' => '#E4EEF9', 'fg' => '#2E6CA8',
                'title' => trans_choice('{1}:count cikgu belum menyumbang kandungan|[2,*]:count cikgu belum menyumbang kandungan', $silentTeachers, ['count' => $silentTeachers]),
                'desc' => __('Belum memuat naik sebarang video, bahan atau kuiz.'),
                'url' => route('admin.bakat'),
            ];
        }

        if ($items === []) {
            $items[] = [
                'icon' => 'check', 'bg' => '#DCF2EE', 'fg' => '#0F7A68',
                'title' => __('Tiada tindakan menunggu'),
                'desc' => __('Semua cikgu aktif dan menyumbang. Platform teratur.'),
                'url' => null,
            ];
        }

        return $items;
    }
}
