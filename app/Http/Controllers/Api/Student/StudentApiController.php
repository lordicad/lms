<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Shared helpers for the student mobile content API. The web app keeps the "active grade"
 * in the session; a stateless API takes it as an optional `?grade=<level>` query param and
 * otherwise falls back to the student's own Tahun.
 */
abstract class StudentApiController extends Controller
{
    protected function resolveGrade(Request $request, User $user): ?Grade
    {
        $level = $request->integer('grade');

        if ($level && ($grade = Grade::where('level', $level)->first())) {
            return $grade;
        }

        return $user->grade;
    }

    /**
     * The compact lesson payload used by every rail and list. Reads this student's progress
     * and favourite off the relations eager-loaded by Lesson::withStudentContext().
     *
     * @return array<string, mixed>
     */
    protected function lessonCard(Lesson $lesson): array
    {
        $progress = $lesson->relationLoaded('progress') ? $lesson->progress->first() : null;

        return [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'thumbnail_url' => $lesson->thumbnailUrl(),
            'duration_label' => $lesson->durationLabel(),
            'source' => $lesson->source,
            'subject_name' => $lesson->chapter?->subject?->displayName(),
            'chapter_label' => $lesson->chapter?->label(),
            'subject_color' => $lesson->chapter?->subject?->color,
            'percent' => $progress?->percent ?? 0,
            'completed' => (bool) ($progress?->completed ?? false),
            'favourited' => $lesson->relationLoaded('favourites') && $lesson->favourites->isNotEmpty(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function subjectCard(Subject $subject): array
    {
        return [
            'id' => $subject->id,
            'name' => $subject->name,
            'short_name' => $subject->short_name,
            'display_name' => $subject->displayName(),
            'slug' => $subject->slug,
            'category' => $subject->category,
            'color' => $subject->color,
            'icon' => $subject->icon,
            'lessons_count' => $subject->lessons_count ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function materialCard(Material $material): array
    {
        return [
            'id' => $material->id,
            'title' => $material->title,
            'icon_name' => $material->iconName(),
            'extension' => $material->extension(),
            'human_size' => $material->humanSize(),
            'download_url' => route('muat-turun.bahan', $material),
            'file_name' => $material->original_name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function gradePayload(Grade $grade): array
    {
        return ['id' => $grade->id, 'level' => $grade->level, 'name' => $grade->name];
    }
}
