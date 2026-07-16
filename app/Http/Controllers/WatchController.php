<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WatchController extends Controller
{
    public function show(Request $request, Lesson $lesson): View
    {
        $this->authorize('view', $lesson);

        $lesson->load('chapter.subject', 'chapter.grade', 'teacher', 'materials');

        $quizzes = $lesson->chapter
            ->quizzes()
            ->published()
            ->orderBy('id')
            ->get();

        $user = $request->user();

        return view('belajar.tonton', [
            'lesson' => $lesson,
            'chapter' => $lesson->chapter,
            'subject' => $lesson->chapter->subject,
            'grade' => $lesson->chapter->grade,
            'materials' => $lesson->materials,
            'quizzes' => $quizzes,
            'previous' => $lesson->previousInChapter(),
            'next' => $lesson->nextInChapter(),
            // Progress + favourite are student-only concerns; a teacher previewing gets neither.
            'progress' => $user->isStudent() ? $lesson->progressFor($user) : null,
            'favourited' => $user->isStudent() && $lesson->isFavouritedBy($user),
        ]);
    }

    /**
     * Called once by the watch page after the student starts playing.
     *
     * The unique index on (lesson_id, student_id) is what makes this idempotent: a student
     * who rewatches a video is still one view. Teachers viewing their own content never
     * count, so the stats stay honest.
     */
    public function markViewed(Request $request, Lesson $lesson): JsonResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return response()->json(['counted' => false]);
        }

        $view = LessonView::firstOrCreate([
            'lesson_id' => $lesson->id,
            'student_id' => $user->id,
        ]);

        if ($view->wasRecentlyCreated) {
            $lesson->increment('views_count');
        }

        return response()->json([
            'counted' => $view->wasRecentlyCreated,
            'views' => $lesson->fresh()->views_count,
        ]);
    }
}
