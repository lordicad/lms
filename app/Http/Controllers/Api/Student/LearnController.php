<?php

namespace App\Http\Controllers\Api\Student;

use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Subject;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Student browse surface for the mobile app: dashboard, subject index, chapter lists and
 * chapter contents. Mirrors the Blade controllers (StudentDashboardController,
 * SubjectIndexController, SubjectBrowseController, ChapterBrowseController) as JSON.
 */
class LearnController extends StudentApiController
{
    public function dashboard(Request $request, LeaderboardService $leaderboard): JsonResponse
    {
        $user = $request->user();
        $grade = $this->resolveGrade($request, $user);

        if (! $grade) {
            return response()->json(['grade' => null, 'subjects' => [], 'continue_watching' => [], 'newest' => []]);
        }

        $gradeId = $grade->id;
        $myRow = $leaderboard->rowFor($user);

        $continue = Lesson::published()
            ->join('lesson_progress', 'lesson_progress.lesson_id', '=', 'lessons.id')
            ->where('lesson_progress.student_id', $user->id)
            ->where('lesson_progress.completed', false)
            ->whereBetween('lesson_progress.percent', [LessonProgress::RESUME_MIN, 89])
            ->whereHas('chapter', fn ($query) => $query
                ->where('grade_id', $gradeId)
                ->where('is_active', true))
            ->orderByDesc('lesson_progress.last_watched_at')
            ->select('lessons.*')
            ->withStudentContext($user)
            ->limit(12)
            ->get();

        $newest = Lesson::published()
            ->whereHas('chapter', fn ($q) => $q->where('grade_id', $gradeId)->where('is_active', true))
            ->withStudentContext($user)
            ->latest('id')
            ->limit(12)
            ->get();

        $subjects = $grade->subjects()
            ->orderBy('sort_order')
            ->withCount(['lessons as lessons_count' => fn ($q) => $q
                ->where('lessons.is_published', true)
                ->where('chapters.grade_id', $gradeId)])
            ->get();

        return response()->json([
            'grade' => $this->gradePayload($grade),
            'points' => $myRow->points ?? 0,
            'rank' => $myRow->rank ?? null,
            'continue_watching' => $continue->map(fn ($l) => $this->lessonCard($l))->all(),
            'newest' => $newest->map(fn ($l) => $this->lessonCard($l))->all(),
            'subjects' => $subjects->map(fn ($s) => $this->subjectCard($s))->all(),
        ]);
    }

    public function subjects(Request $request): JsonResponse
    {
        $user = $request->user();
        $grade = $this->resolveGrade($request, $user);

        if (! $grade) {
            return response()->json(['grade' => null, 'categories' => []]);
        }

        $categories = $grade->subjectsByCategory()
            ->map(fn ($subjects, $key) => [
                'key' => $key,
                'label' => Subject::categoryLabel($key),
                'subjects' => $subjects->map(fn ($s) => $this->subjectCard($s))->all(),
            ])
            ->values();

        return response()->json([
            'grade' => $this->gradePayload($grade),
            'categories' => $categories,
        ]);
    }

    public function subjectChapters(Request $request, Subject $subject): JsonResponse
    {
        $user = $request->user();
        $grade = $this->resolveGrade($request, $user);

        if (! $grade) {
            return response()->json(['message' => 'Tiada Tahun aktif.'], 422);
        }

        $chapters = $subject->chapters()
            ->where('grade_id', $grade->id)
            ->active()
            ->ordered()
            ->withCount([
                'lessons as lessons_count' => fn ($q) => $q->where('is_published', true),
                'materials as materials_count',
                'quizzes as quizzes_count' => fn ($q) => $q->where('is_published', true),
            ])
            ->get();

        $watchedByChapter = $user->isStudent()
            ? $user->lessonViews()
                ->join('lessons', 'lessons.id', '=', 'lesson_views.lesson_id')
                ->where('lessons.is_published', true)
                ->whereIn('lessons.chapter_id', $chapters->pluck('id'))
                ->selectRaw('lessons.chapter_id, COUNT(*) as watched')
                ->groupBy('lessons.chapter_id')
                ->pluck('watched', 'lessons.chapter_id')
            : collect();

        return response()->json([
            'subject' => $this->subjectCard($subject),
            'grade' => $this->gradePayload($grade),
            'chapters' => $chapters->map(fn ($c) => [
                'id' => $c->id,
                'number' => $c->number,
                'title' => $c->title,
                'label' => $c->label(),
                'lessons_count' => $c->lessons_count,
                'materials_count' => $c->materials_count,
                'quizzes_count' => $c->quizzes_count,
                'watched_count' => (int) ($watchedByChapter[$c->id] ?? 0),
            ])->all(),
        ]);
    }

    public function chapter(Request $request, Chapter $chapter): JsonResponse
    {
        $user = $request->user();
        $chapter->load('subject', 'grade');

        $lessons = $chapter->lessons()->published()->orderBy('id')->withStudentContext($user)->get();

        $watchedIds = $user->isStudent()
            ? $user->lessonViews()->whereIn('lesson_id', $lessons->pluck('id'))->pluck('lesson_id')->all()
            : [];

        $quizzes = $chapter->quizzes()
            ->published()
            ->withCount(['attempts as my_attempts_count' => fn ($q) => $q->where('student_id', $user->id)])
            ->orderBy('id')
            ->get();

        return response()->json([
            'chapter' => [
                'id' => $chapter->id,
                'number' => $chapter->number,
                'title' => $chapter->title,
                'label' => $chapter->label(),
                'description' => $chapter->description,
            ],
            'subject' => $this->subjectCard($chapter->subject),
            'grade' => $this->gradePayload($chapter->grade),
            'lessons' => $lessons->map(fn ($l) => array_merge($this->lessonCard($l), [
                'watched' => in_array($l->id, $watchedIds, true),
            ]))->all(),
            'materials' => $chapter->materials()->orderBy('id')->get()->map(fn ($m) => $this->materialCard($m))->all(),
            'quizzes' => $quizzes->map(fn ($q) => [
                'id' => $q->id,
                'title' => $q->title,
                'type' => $q->type,
                'my_attempts_count' => $q->my_attempts_count,
            ])->all(),
        ]);
    }
}
