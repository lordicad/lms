<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonView;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
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

    /** All-time platform totals for the four summary cards. */
    public function totals(): array
    {
        return [
            'students' => User::where('role', User::ROLE_STUDENT)->count(),
            'teachers' => User::where('role', User::ROLE_TEACHER)->count(),
            'videos' => Lesson::count(),
            'quizzes' => Quiz::count(),
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
        return User::where('role', User::ROLE_TEACHER)
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
        $video = Lesson::with('teacher:id,name')
            ->orderByDesc('views_count')->orderBy('id')->first();

        $material = Material::with('teacher:id,name')
            ->orderByDesc('download_count')->orderBy('id')->first();

        $quiz = Quiz::with('teacher:id,name')
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
        $completed = $this->bucketed(QuizAttempt::query()->completed(), 'completed_at', $keys, $monthly, $start);
        $passed = $this->bucketed(QuizAttempt::query()->completed()->passed(), 'completed_at', $keys, $monthly, $start);

        $lessons = $this->bucketed(Lesson::query(), 'created_at', $keys, $monthly, $start);
        $materials = $this->bucketed(Material::query(), 'created_at', $keys, $monthly, $start);
        $quizzes = $this->bucketed(Quiz::query(), 'created_at', $keys, $monthly, $start);
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
        return User::whereIn('role', [User::ROLE_STUDENT, User::ROLE_TEACHER])
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->with('grade')
            ->latest('created_at');
    }

    /**
     * Real oversight signals the admin can act on — never invented. Only non-empty ones show; if the
     * platform is entirely tidy a single "all clear" item is returned. Shared by the dashboard and
     * the exports so the report matches the page.
     *
     * @return array<int, array{icon:string,bg:string,title:string,desc:string}>
     */
    public function pending(): array
    {
        $inactiveTeachers = User::where('role', User::ROLE_TEACHER)->where('is_active', false)->count();
        $draftVideos = Lesson::where('is_published', false)->count();
        $silentTeachers = User::where('role', User::ROLE_TEACHER)
            ->whereDoesntHave('lessons')
            ->whereDoesntHave('materials')
            ->whereDoesntHave('quizzes')
            ->count();

        $items = [];

        if ($inactiveTeachers > 0) {
            $items[] = [
                'icon' => '🚫', 'bg' => '#FDE7E0',
                'title' => trans_choice('{1}:count akaun cikgu dinyahaktifkan|[2,*]:count akaun cikgu dinyahaktifkan', $inactiveTeachers, ['count' => $inactiveTeachers]),
                'desc' => __('Kandungan mereka kekal untuk murid. Semak di halaman Cikgu.'),
            ];
        }

        if ($draftVideos > 0) {
            $items[] = [
                'icon' => '📼', 'bg' => '#FEF0CE',
                'title' => trans_choice('{1}:count video belum diterbitkan|[2,*]:count video belum diterbitkan', $draftVideos, ['count' => $draftVideos]),
                'desc' => __('Draf yang belum kelihatan kepada murid.'),
            ];
        }

        if ($silentTeachers > 0) {
            $items[] = [
                'icon' => '🧑‍🏫', 'bg' => '#E4EEF9',
                'title' => trans_choice('{1}:count cikgu belum menyumbang kandungan|[2,*]:count cikgu belum menyumbang kandungan', $silentTeachers, ['count' => $silentTeachers]),
                'desc' => __('Belum memuat naik sebarang video, bahan atau kuiz.'),
            ];
        }

        if ($items === []) {
            $items[] = [
                'icon' => '✅', 'bg' => '#DCF2EE',
                'title' => __('Tiada tindakan menunggu'),
                'desc' => __('Semua cikgu aktif dan menyumbang. Platform teratur.'),
            ];
        }

        return $items;
    }
}
