<?php

namespace App\Http\Controllers\Cikgu;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class QuizStatsController extends Controller
{
    /**
     * Per-quiz statistics: who attempted, the average score, and the correct-rate for each
     * question so a teacher can see which concept the class actually missed.
     */
    public function __invoke(Quiz $quiz): View
    {
        $this->authorize('viewStats', $quiz);

        abort_unless($quiz->isInteractive(), Response::HTTP_NOT_FOUND);

        $quiz->load('chapter.subject', 'chapter.grade', 'questions');

        $attempts = $quiz->attempts()
            ->completed()
            ->with('student.grade')
            ->orderByDesc('completed_at')
            ->get();

        // Correct-rate per question, counted across every completed attempt.
        $perQuestion = DB::table('attempt_answers')
            ->join('quiz_attempts', 'quiz_attempts.id', '=', 'attempt_answers.quiz_attempt_id')
            ->where('quiz_attempts.quiz_id', $quiz->id)
            ->whereNotNull('quiz_attempts.completed_at')
            ->groupBy('attempt_answers.question_id')
            ->select([
                'attempt_answers.question_id',
                DB::raw('COUNT(*) as answered'),
                DB::raw('SUM(attempt_answers.is_correct) as correct'),
            ])
            ->get()
            ->keyBy('question_id');

        $completed = $attempts->count();

        return view('cikgu.kuiz.statistik', [
            'quiz' => $quiz,
            'chapter' => $quiz->chapter,
            'subject' => $quiz->chapter->subject,
            'attempts' => $attempts,
            'completedCount' => $completed,
            'averageScore' => $completed > 0 ? round($attempts->avg('score'), 1) : 0,
            'averagePercent' => $completed > 0 ? (int) round($attempts->avg(fn ($a) => $a->percentage())) : 0,
            'perQuestion' => $perQuestion,
        ]);
    }
}
