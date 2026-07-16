<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectBrowseController extends Controller
{
    /**
     * Chapter list for one Subject x Tahun, with per-chapter content counts and, for a
     * student, how many of the videos they have already watched.
     */
    public function __invoke(Request $request, Subject $subject, Grade $grade): View
    {
        $user = $request->user();

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

        // How many published lessons in each chapter this student has already viewed.
        $watchedByChapter = collect();

        if ($user->isStudent()) {
            $watchedByChapter = $user->lessonViews()
                ->join('lessons', 'lessons.id', '=', 'lesson_views.lesson_id')
                ->where('lessons.is_published', true)
                ->whereIn('lessons.chapter_id', $chapters->pluck('id'))
                ->selectRaw('lessons.chapter_id, COUNT(*) as watched')
                ->groupBy('lessons.chapter_id')
                ->pluck('watched', 'lessons.chapter_id');
        }

        return view('belajar.subjek', [
            'subject' => $subject,
            'grade' => $grade,
            'grades' => Grade::orderBy('level')->get(),
            'chapters' => $chapters,
            'watchedByChapter' => $watchedByChapter,
        ]);
    }
}
