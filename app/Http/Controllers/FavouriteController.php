<?php

namespace App\Http\Controllers;

use App\Models\Favourite;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavouriteController extends Controller
{
    /**
     * The student's saved lessons.
     */
    public function index(Request $request): View
    {
        $student = $request->user();

        $lessons = Lesson::published()
            ->join('favourites', 'favourites.lesson_id', '=', 'lessons.id')
            ->where('favourites.student_id', $student->id)
            ->orderByDesc('favourites.created_at')
            ->with('chapter.subject', 'chapter.grade')
            ->select('lessons.*')
            ->get();

        return view('belajar.kegemaran', ['lessons' => $lessons]);
    }

    /**
     * Add to favourites. Idempotent: favouriting an already-favourited lesson changes nothing,
     * and the denormalised counter only moves when a row is actually created.
     */
    public function store(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorize('view', $lesson);

        $favourite = Favourite::firstOrCreate([
            'student_id' => $request->user()->id,
            'lesson_id' => $lesson->id,
        ]);

        if ($favourite->wasRecentlyCreated) {
            $lesson->increment('favourites_count');
        }

        return response()->json([
            'favourited' => true,
            'count' => $lesson->fresh()->favourites_count,
        ]);
    }

    public function destroy(Request $request, Lesson $lesson): JsonResponse
    {
        $deleted = Favourite::where('student_id', $request->user()->id)
            ->where('lesson_id', $lesson->id)
            ->delete();

        if ($deleted) {
            // Guard the counter against ever going negative.
            $lesson->decrement('favourites_count');
            $lesson->where('favourites_count', '<', 0)->update(['favourites_count' => 0]);
        }

        return response()->json([
            'favourited' => false,
            'count' => max(0, $lesson->fresh()->favourites_count),
        ]);
    }
}
