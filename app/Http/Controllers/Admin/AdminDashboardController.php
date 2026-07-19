<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Favourite;
use App\Models\Lesson;
use App\Models\LessonView;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * The admin landing page — a calm, read-only overview of the whole platform. Every number here is a
 * real aggregate: the headline counts, the week-over-week deltas, today's activity and the pending
 * oversight signals all come from live queries, so nothing on this page is decorative.
 */
class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $weekAgo = Carbon::now()->subDays(7);
        $today = Carbon::today();

        $newStudents = User::where('role', User::ROLE_STUDENT)->where('created_at', '>=', $weekAgo)->count();
        $newTeachers = User::where('role', User::ROLE_TEACHER)->where('created_at', '>=', $weekAgo)->count();
        $newVideos = Lesson::where('created_at', '>=', $weekAgo)->count();
        $newQuizzes = Quiz::where('created_at', '>=', $weekAgo)->count();

        // Today's activity, normalised so the widest bar reflects the busiest signal.
        $viewsToday = LessonView::where('created_at', '>=', $today)->count();
        $attemptsToday = QuizAttempt::completed()->where('completed_at', '>=', $today)->count();
        $passesToday = QuizAttempt::completed()->passed()->where('completed_at', '>=', $today)->count();
        $uploadsWeek = Lesson::where('created_at', '>=', $weekAgo)->count()
            + Material::where('created_at', '>=', $weekAgo)->count();

        $activity = collect([
            ['label' => __('Video ditonton hari ini'), 'value' => $viewsToday, 'color' => '#17907B'],
            ['label' => __('Percubaan kuiz hari ini'), 'value' => $attemptsToday, 'color' => '#4276AE'],
            ['label' => __('Kuiz lulus hari ini'), 'value' => $passesToday, 'color' => '#E3A31C'],
            ['label' => __('Muat naik minggu ini'), 'value' => $uploadsWeek, 'color' => '#B84A75'],
        ]);
        $peak = max($activity->max('value'), 1);

        return view('admin.dashboard', [
            'totalStudents' => User::where('role', User::ROLE_STUDENT)->count(),
            'totalTeachers' => User::where('role', User::ROLE_TEACHER)->count(),
            'totalVideos' => Lesson::count(),
            'totalQuizzes' => Quiz::count(),

            'newStudents' => $newStudents,
            'newTeachers' => $newTeachers,
            'newVideos' => $newVideos,
            'newQuizzes' => $newQuizzes,

            // The five most recent sign-ups (teacher or student), each with its grade for the caption.
            'recentUsers' => User::whereIn('role', [User::ROLE_STUDENT, User::ROLE_TEACHER])
                ->with('grade')
                ->latest('created_at')
                ->take(5)
                ->get(),

            'activity' => $activity->map(fn (array $a): array => [
                ...$a,
                'width' => $a['value'] > 0 ? max(round($a['value'] / $peak * 100), 6).'%' : '0%',
            ]),

            'pending' => $this->pending(),
        ]);
    }

    /**
     * Real oversight signals the admin can act on — never invented. Only non-empty ones show; if the
     * platform is entirely tidy the page says so rather than displaying three zeros.
     *
     * @return array<int, array{icon:string,bg:string,title:string,desc:string}>
     */
    private function pending(): array
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
