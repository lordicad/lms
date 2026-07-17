<?php

namespace App\Http\Controllers\Api\Student;

use App\Models\Favourite;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Saved lessons for the mobile app: list the student's favourites and toggle one.
 * Mirrors the web FavouriteController as JSON, but exposes a single idempotent
 * toggle rather than separate store/destroy so the client keeps one action.
 */
class FavouriteController extends StudentApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return response()->json(['lessons' => []]);
        }

        $lessons = Lesson::published()
            ->join('favourites', 'favourites.lesson_id', '=', 'lessons.id')
            ->where('favourites.student_id', $user->id)
            ->orderByDesc('favourites.created_at')
            ->select('lessons.*')
            ->withStudentContext($user)
            ->get();

        return response()->json([
            'lessons' => $lessons->map(fn ($l) => $this->lessonCard($l))->all(),
        ]);
    }

    public function toggle(Request $request, Lesson $lesson): JsonResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return response()->json(['favourited' => false, 'count' => (int) $lesson->favourites_count], 403);
        }

        if (! $lesson->is_published) {
            return response()->json(['message' => 'Video tidak tersedia.'], 404);
        }

        $existing = Favourite::where('student_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $lesson->decrement('favourites_count');
            Lesson::where('id', $lesson->id)
                ->where('favourites_count', '<', 0)
                ->update(['favourites_count' => 0]);
            $favourited = false;
        } else {
            Favourite::create([
                'student_id' => $user->id,
                'lesson_id' => $lesson->id,
            ]);
            $lesson->increment('favourites_count');
            $favourited = true;
        }

        return response()->json([
            'favourited' => $favourited,
            'count' => max(0, (int) $lesson->fresh()->favourites_count),
        ]);
    }
}
