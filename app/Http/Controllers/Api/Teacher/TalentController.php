<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Services\TalentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The teacher's own transparent content-engagement signal, mirroring Cikgu\TalentController. */
class TalentController extends Controller
{
    public function __invoke(Request $request, TalentService $talent): JsonResponse
    {
        $teacher = $request->user();

        if (! $teacher || ! $teacher->isTeacher()) {
            return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
        }

        $result = $talent->forTeacher($teacher);
        $topVideos = $teacher->lessons()
            ->with('chapter.subject')
            ->orderByDesc('views_count')
            ->take(5)
            ->get();
        $topMaterials = $teacher->materials()
            ->with('chapter.subject')
            ->orderByDesc('download_count')
            ->take(5)
            ->get();
        $topQuizzes = $teacher->quizzes()
            ->with('chapter.subject')
            ->withCount('completedAttempts')
            ->orderByDesc('completed_attempts_count')
            ->take(5)
            ->get();
        $topFavourites = $result->lessons->sortByDesc('favourites')->take(5)->values();
        $quizIds = $teacher->quizzes()->pluck('id');

        return response()->json([
            'signal' => [
                'headline' => $result->headline,
                'sufficient' => (bool) $result->sufficient,
                'engaged_students' => (int) $result->engaged_students,
                'engagement' => round((float) $result->raw['engagement'], 1),
                'quality' => round((float) $result->raw['quality'] * 100, 1),
                'breadth' => (int) $result->raw['breadth'],
                'outcome' => $result->raw['outcome'],
            ],
            'stats' => [
                'views' => (int) $teacher->lessons()->sum('views_count'),
                'favourites' => (int) $result->lessons->sum('favourites'),
                'downloads' => (int) $teacher->materials()->sum('download_count'),
                'attempts' => QuizAttempt::whereIn('quiz_id', $quizIds)->completed()->count(),
            ],
            'leaderboards' => [
                [
                    'kind' => 'views',
                    'title' => 'Video paling ditonton',
                    'items' => $topVideos->map(fn ($lesson) => $this->item(
                        $lesson->title,
                        $lesson->chapter?->subject?->displayName(),
                        $lesson->chapter?->label(),
                        (int) $lesson->views_count,
                    ))->all(),
                ],
                [
                    'kind' => 'favourites',
                    'title' => 'Video paling digemari',
                    'items' => $topFavourites->map(fn ($entry) => $this->item(
                        $entry->lesson->title,
                        $entry->lesson->chapter?->subject?->displayName(),
                        $entry->lesson->chapter?->label(),
                        (int) $entry->favourites,
                    ))->all(),
                ],
                [
                    'kind' => 'downloads',
                    'title' => 'Bahan paling dimuat turun',
                    'items' => $topMaterials->map(fn ($material) => $this->item(
                        $material->title,
                        $material->chapter?->subject?->displayName(),
                        $material->chapter?->label(),
                        (int) $material->download_count,
                    ))->all(),
                ],
                [
                    'kind' => 'attempts',
                    'title' => 'Kuiz paling dicuba',
                    'items' => $topQuizzes->map(fn ($quiz) => $this->item(
                        $quiz->title,
                        $quiz->chapter?->subject?->displayName(),
                        $quiz->chapter?->label(),
                        (int) $quiz->completed_attempts_count,
                    ))->all(),
                ],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function item(string $title, ?string $subjectName, ?string $chapterLabel, int $value): array
    {
        return [
            'title' => $title,
            'subject_name' => $subjectName,
            'chapter_label' => $chapterLabel,
            'value' => $value,
        ];
    }
}
