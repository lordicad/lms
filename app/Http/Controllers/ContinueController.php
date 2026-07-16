<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContinueController extends Controller
{
    /**
     * Everything the student has started but not finished, newest first — the full-page version
     * of the home "Sambung Menonton" rail.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $lessons = Lesson::published()
            ->join('lesson_progress', 'lesson_progress.lesson_id', '=', 'lessons.id')
            ->where('lesson_progress.student_id', $user->id)
            ->where('lesson_progress.completed', false)
            ->whereBetween('lesson_progress.percent', [LessonProgress::RESUME_MIN, 89])
            ->orderByDesc('lesson_progress.last_watched_at')
            ->select('lessons.*')
            ->withStudentContext($user)
            ->get();

        return view('belajar.sambung', ['lessons' => $lessons]);
    }
}
