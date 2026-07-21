<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_index_renders_and_chapter_filter_narrows_results(): void
    {
        $grade = Grade::factory()->level(6)->create();
        $subject = Subject::factory()->availableIn($grade)->create();
        $chapterA = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);
        $chapterB = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);

        $teacher = User::factory()->teacher()->create();
        $keep = Lesson::factory()->for($chapterA)->create(['teacher_id' => $teacher->id]);
        Lesson::factory()->for($chapterB)->create(['teacher_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->get(route('cikgu.video.index', [
            'tahun' => 6, 'subjek' => $subject->slug, 'bab' => $chapterA->id,
        ]));

        $response->assertOk();
        $this->assertEquals([$keep->id], $response->viewData('lessons')->pluck('id')->all());
    }

    public function test_quiz_index_chapter_filter_narrows_results(): void
    {
        $grade = Grade::factory()->level(5)->create();
        $subject = Subject::factory()->availableIn($grade)->create();
        $chapterA = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);
        $chapterB = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);

        $teacher = User::factory()->teacher()->create();
        $keep = Quiz::factory()->for($chapterA)->create(['teacher_id' => $teacher->id]);
        Quiz::factory()->for($chapterB)->create(['teacher_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->get(route('cikgu.kuiz.index', [
            'tahun' => 5, 'subjek' => $subject->slug, 'bab' => $chapterA->id,
        ]));

        $response->assertOk();
        $this->assertEquals([$keep->id], $response->viewData('quizzes')->pluck('id')->all());
    }
}
