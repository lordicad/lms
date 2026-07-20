<?php

namespace App\Http\Controllers\Api\Student;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A small, grade-scoped catalogue search for the mobile learner. It returns only
 * content the current student could open, never unpublished teacher drafts.
 */
class SearchController extends StudentApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ], [
            'q.required' => 'Sila masukkan kata carian.',
            'q.min' => 'Masukkan sekurang-kurangnya 2 aksara untuk mencari.',
        ]);

        $user = $request->user();
        $grade = $this->resolveGrade($request, $user);
        $term = trim($data['q']);

        if (! $grade || $term === '') {
            return response()->json(['results' => []]);
        }

        $lessons = Lesson::published()
            ->whereHas('chapter', fn ($query) => $query
                ->where('grade_id', $grade->id)
                ->where('is_active', true))
            ->where(fn ($query) => $query
                ->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%"))
            ->withStudentContext($user)
            ->latest('id')
            ->limit(15)
            ->get();

        $quizzes = Quiz::published()
            ->whereHas('chapter', fn ($query) => $query
                ->where('grade_id', $grade->id)
                ->where('is_active', true))
            ->where(fn ($query) => $query
                ->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%"))
            ->with('chapter.subject')
            ->withCount('questions')
            ->latest('id')
            ->limit(15)
            ->get();

        $rankedByQuiz = QuizAttempt::where('student_id', $user->id)
            ->where('counts_for_ranking', true)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->get()
            ->keyBy('quiz_id');

        $results = [
            ...$lessons->map(fn (Lesson $lesson) => [
                'id' => $lesson->id,
                'kind' => 'lesson',
                'title' => $lesson->title,
                'subject_name' => $lesson->chapter?->subject?->displayName(),
                'chapter_label' => $lesson->chapter?->label(),
                'thumbnail_url' => $lesson->thumbnailUrl(),
                'percent' => $lesson->progress->first()?->percent ?? 0,
                'completed' => (bool) ($lesson->progress->first()?->completed ?? false),
            ])->all(),
            ...$quizzes->map(function (Quiz $quiz) use ($rankedByQuiz) {
                $ranked = $rankedByQuiz->get($quiz->id);

                return [
                    'id' => $quiz->id,
                    'kind' => 'quiz',
                    'title' => $quiz->title,
                    'subject_name' => $quiz->chapter?->subject?->displayName(),
                    'chapter_label' => $quiz->chapter?->label(),
                    'quiz_type' => $quiz->type,
                    'question_count' => $quiz->questions_count,
                    'percent' => $ranked?->percentage(),
                    'completed' => $ranked !== null,
                ];
            })->all(),
        ];

        return response()->json(['results' => $results]);
    }
}
