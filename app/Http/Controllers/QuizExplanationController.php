<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuizAttempt;
use App\Services\AnswerExplainer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class QuizExplanationController extends Controller
{
    public function __construct(private readonly AnswerExplainer $explainer) {}

    /**
     * Return an AI explanation for one question the student got wrong, on the review page. Cached
     * after the first call (see {@see AnswerExplainer}), so repeat views are instant and free.
     */
    public function show(Request $request, QuizAttempt $attempt, Question $question): JsonResponse
    {
        // A student may only explain their own finished attempt.
        abort_unless($attempt->student_id === $request->user()->id, Response::HTTP_FORBIDDEN);
        abort_unless($attempt->isCompleted(), Response::HTTP_FORBIDDEN);

        // The question must belong to the quiz this attempt was taken on.
        abort_unless($question->quiz_id === $attempt->quiz_id, Response::HTTP_NOT_FOUND);

        if (! $this->explainer->enabled()) {
            return response()->json(['message' => __('Penerangan AI tidak tersedia.')], 422);
        }

        $answer = $attempt->answers()->where('question_id', $question->id)->first();

        // Only wrong answers get an explanation - there is nothing to explain about a right one,
        // and an unanswered question was never really attempted.
        abort_if($answer === null, Response::HTTP_NOT_FOUND);
        abort_if($answer->is_correct, Response::HTTP_NOT_FOUND);

        $answer->setRelation('question', $question);

        try {
            $explanation = $this->explainer->explain($answer, app()->getLocale());
        } catch (Throwable $e) {
            return response()->json(['message' => __('Penerangan gagal dijana. Sila cuba lagi.')], 502);
        }

        return response()->json(['explanation' => $explanation]);
    }
}
