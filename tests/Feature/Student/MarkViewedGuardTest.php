<?php

namespace Tests\Feature\Student;

use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Recording a view must use the same gate as opening the video: a student can only mark a lesson
 * viewed if they could actually view it. Otherwise a crafted POST would count views on an
 * unpublished draft the watch page itself hides - see WatchController/Api LessonController::show.
 */
class MarkViewedGuardTest extends TestCase
{
    use RefreshDatabase;

    private function draft(): Lesson
    {
        return Lesson::factory()->for(Chapter::factory()->create())->create([
            'is_published' => false,
            'views_count' => 0,
        ]);
    }

    private function published(): Lesson
    {
        return Lesson::factory()->for(Chapter::factory()->create())->create([
            'is_published' => true,
            'views_count' => 0,
        ]);
    }

    public function test_web_a_student_cannot_mark_an_unpublished_lesson_viewed(): void
    {
        $lesson = $this->draft();

        $this->actingAs(User::factory()->student(3)->create())
            ->post(route('video.tonton', $lesson))
            ->assertForbidden();

        $this->assertSame(0, $lesson->fresh()->views_count);
        $this->assertSame(0, LessonView::where('lesson_id', $lesson->id)->count());
    }

    public function test_web_a_published_lesson_still_counts_one_view(): void
    {
        $lesson = $this->published();

        $this->actingAs(User::factory()->student(3)->create())
            ->post(route('video.tonton', $lesson))
            ->assertOk()
            ->assertJson(['counted' => true, 'views' => 1]);

        $this->assertSame(1, $lesson->fresh()->views_count);
    }

    public function test_api_a_student_cannot_mark_an_unpublished_lesson_viewed(): void
    {
        $lesson = $this->draft();

        Sanctum::actingAs(User::factory()->student(3)->create());
        $this->postJson("/api/student/lessons/{$lesson->id}/viewed")->assertNotFound();

        $this->assertSame(0, $lesson->fresh()->views_count);
        $this->assertSame(0, LessonView::where('lesson_id', $lesson->id)->count());
    }

    public function test_api_a_published_lesson_still_counts_one_view(): void
    {
        $lesson = $this->published();

        Sanctum::actingAs(User::factory()->student(3)->create());
        $this->postJson("/api/student/lessons/{$lesson->id}/viewed")
            ->assertOk()
            ->assertJson(['counted' => true, 'views' => 1]);

        $this->assertSame(1, $lesson->fresh()->views_count);
    }

    public function test_api_progress_cannot_be_saved_against_an_unpublished_lesson(): void
    {
        // The draft has no duration yet - a successful write would both create a progress row and
        // stamp this student-supplied duration onto the teacher's draft. Neither may happen.
        $lesson = $this->draft();
        $lesson->forceFill(['duration_seconds' => null])->save();

        Sanctum::actingAs(User::factory()->student(3)->create());
        $this->postJson("/api/student/lessons/{$lesson->id}/progress", [
            'position_seconds' => 120,
            'duration_seconds' => 600,
        ])->assertNotFound();

        $this->assertSame(0, LessonProgress::where('lesson_id', $lesson->id)->count());
        $this->assertNull($lesson->fresh()->duration_seconds);
    }

    public function test_api_progress_saves_on_a_published_lesson(): void
    {
        $lesson = $this->published();

        Sanctum::actingAs(User::factory()->student(3)->create());
        $this->postJson("/api/student/lessons/{$lesson->id}/progress", [
            'position_seconds' => 300,
            'duration_seconds' => 600,
        ])->assertOk()->assertJson(['saved' => true, 'percent' => 50]);

        $this->assertSame(1, LessonProgress::where('lesson_id', $lesson->id)->count());
    }
}
