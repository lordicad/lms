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

        // Summaries are computed over EVERY completed attempt via an aggregate query, so paginating
        // the list below never skews the average.
        $aggregate = $quiz->attempts()->completed()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('AVG(score) as avg_score')
            ->selectRaw('AVG(CASE WHEN max_score > 0 THEN (score / max_score) * 100 ELSE 0 END) as avg_percent')
            ->first();

        // The attempt list itself paginates at 10; each completed attempt stays a separate numbered
        // row (retries are not deduplicated). Newest completed first.
        $attempts = $quiz->attempts()
            ->completed()
            ->with('student.grade')
            ->orderByDesc('completed_at')
            ->paginate(10)
            ->withQueryString();

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

        $completed = (int) ($aggregate->total ?? 0);

        return view('cikgu.kuiz.statistik', [
            'quiz' => $quiz,
            'chapter' => $quiz->chapter,
            'subject' => $quiz->chapter->subject,
            'attempts' => $attempts,
            'completedCount' => $completed,
            'averageScore' => $completed > 0 ? round((float) $aggregate->avg_score, 1) : 0,
            'averagePercent' => $completed > 0 ? (int) round((float) $aggregate->avg_percent) : 0,
            'perQuestion' => $perQuestion,
        ]);
    }
}
