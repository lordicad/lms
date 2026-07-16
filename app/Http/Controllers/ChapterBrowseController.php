<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChapterBrowseController extends Controller
{
    /**
     * Everything inside one Bab: the videos, the supporting materials, the quizzes.
     */
    public function __invoke(Request $request, Chapter $chapter): View
    {
        $user = $request->user();

        $chapter->load('subject', 'grade');

        $lessons = $chapter->lessons()
            ->published()
            ->with('teacher')
            ->orderBy('id')
            ->get();

        // "Dah tonton" ticks on the lesson cards.
        $watchedIds = $user->isStudent()
            ? $user->lessonViews()->whereIn('lesson_id', $lessons->pluck('id'))->pluck('lesson_id')
            : collect();

        $quizzes = $chapter->quizzes()
            ->published()
            ->withCount(['attempts as my_attempts_count' => fn ($q) => $q->where('student_id', $user->id)])
            ->orderBy('id')
            ->get();

        return view('belajar.bab', [
            'chapter' => $chapter,
            'subject' => $chapter->subject,
            'grade' => $chapter->grade,
            'lessons' => $lessons,
            'watchedIds' => $watchedIds,
            'materials' => $chapter->materials()->orderBy('id')->get(),
            'quizzes' => $quizzes,
        ]);
    }
}
