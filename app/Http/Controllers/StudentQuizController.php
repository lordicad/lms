<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Support\ActiveGrade;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentQuizController extends Controller
{
    /**
     * Every published quiz in the student's Tahun — attempted and not — with their ranked score
     * where they have finished one.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $grade = ActiveGrade::for($user);

        $quizzes = $grade
            ? Quiz::published()
                ->whereHas('chapter', fn ($q) => $q->where('grade_id', $grade->id)->where('is_active', true))
                ->with('chapter.subject')
                ->withCount('questions')
                ->orderByDesc('id')
                ->get()
            : collect();

        // The ranked (first completed) attempt per quiz, so each row shows a score or "not tried".
        $rankedAttempts = QuizAttempt::where('student_id', $user->id)
            ->where('counts_for_ranking', true)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->get()
            ->keyBy('quiz_id');

        return view('belajar.kuiz-saya', [
            'grade' => $grade,
            'quizzes' => $quizzes,
            'rankedAttempts' => $rankedAttempts,
        ]);
    }
}
