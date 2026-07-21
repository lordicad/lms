<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Teacher content lists for the mobile Content Hub: the teacher's own videos, materials
 * and quizzes, each with publish status. Read-only for now — edit/publish/delete follow.
 */
class ContentController extends Controller
{
    public function videos(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);
        if (! $teacher) {
            return $this->forbidden();
        }

        $lessons = $teacher->lessons()
            ->with('chapter.subject', 'chapter.grade')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'videos' => $lessons->map(fn ($l) => [
                'id' => $l->id,
                'title' => $l->title,
                'description' => $l->description,
                'chapter_id' => $l->chapter_id,
                'subject_id' => $l->chapter?->subject_id,
                'grade_id' => $l->chapter?->grade_id,
                'chapter_label' => $l->chapter?->label(),
                'chapter_number' => $l->chapter?->number,
                'subject_name' => $l->chapter?->subject?->displayName(),
                'grade_name' => $l->chapter?->grade?->name,
                'published' => (bool) $l->is_published,
                'views' => (int) $l->views_count,
                'source' => $l->source,
                'ownership' => $l->ownership,
                'youtube_url' => $l->youtube_id ? "https://youtu.be/{$l->youtube_id}" : null,
                'thumbnail_url' => $l->thumbnailUrl(),
            ])->all(),
        ]);
    }

    public function materials(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);
        if (! $teacher) {
            return $this->forbidden();
        }

        $materials = $teacher->materials()
            ->with('chapter.subject', 'chapter.grade')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'materials' => $materials->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'chapter_id' => $m->chapter_id,
                'subject_id' => $m->chapter?->subject_id,
                'grade_id' => $m->chapter?->grade_id,
                'chapter_label' => $m->chapter?->label(),
                'chapter_number' => $m->chapter?->number,
                'subject_name' => $m->chapter?->subject?->displayName(),
                'grade_name' => $m->chapter?->grade?->name,
                'extension' => $m->extension(),
                'human_size' => $m->humanSize(),
            ])->all(),
        ]);
    }

    public function quizzes(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);
        if (! $teacher) {
            return $this->forbidden();
        }

        $quizzes = $teacher->quizzes()
            ->with('chapter.subject', 'chapter.grade')
            ->withCount(['questions', 'completedAttempts as attempts_count'])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'quizzes' => $quizzes->map(fn ($q) => [
                'id' => $q->id,
                'title' => $q->title,
                'type' => $q->type,
                'chapter_label' => $q->chapter?->label(),
                'chapter_number' => $q->chapter?->number,
                'subject_name' => $q->chapter?->subject?->displayName(),
                'grade_name' => $q->chapter?->grade?->name,
                'published' => (bool) $q->is_published,
                'question_count' => $q->questions_count,
                'attempts' => $q->attempts_count,
            ])->all(),
        ]);
    }

    public function toggleVideo(Request $request, Lesson $lesson): JsonResponse
    {
        $teacher = $this->teacher($request);
        if (! $teacher || $lesson->teacher_id !== $teacher->id) {
            return $this->forbidden();
        }

        $lesson->update(['is_published' => ! $lesson->is_published]);

        return response()->json(['published' => (bool) $lesson->is_published]);
    }

    public function toggleQuiz(Request $request, Quiz $quiz): JsonResponse
    {
        $teacher = $this->teacher($request);
        if (! $teacher || $quiz->teacher_id !== $teacher->id) {
            return $this->forbidden();
        }

        $quiz->update(['is_published' => ! $quiz->is_published]);

        return response()->json(['published' => (bool) $quiz->is_published]);
    }

    public function deleteVideo(Request $request, Lesson $lesson): JsonResponse
    {
        $teacher = $this->teacher($request);
        if (! $teacher || $lesson->teacher_id !== $teacher->id) {
            return $this->forbidden();
        }

        $lesson->deleteFiles();
        $lesson->delete();

        return response()->json(['deleted' => true]);
    }

    public function deleteMaterial(Request $request, Material $material): JsonResponse
    {
        $teacher = $this->teacher($request);
        if (! $teacher || $material->teacher_id !== $teacher->id) {
            return $this->forbidden();
        }

        $material->deleteFile();
        $material->delete();

        return response()->json(['deleted' => true]);
    }

    public function deleteQuiz(Request $request, Quiz $quiz): JsonResponse
    {
        $teacher = $this->teacher($request);
        if (! $teacher || $quiz->teacher_id !== $teacher->id) {
            return $this->forbidden();
        }

        $quiz->deleteFile();
        $quiz->delete(); // questions, options and attempts cascade

        return response()->json(['deleted' => true]);
    }

    private function teacher(Request $request): ?User
    {
        $user = $request->user();

        return $user && $user->isTeacher() ? $user : null;
    }

    private function forbidden(): JsonResponse
    {
        return response()->json(['message' => 'Hanya guru boleh mengakses ini.'], 403);
    }
}
