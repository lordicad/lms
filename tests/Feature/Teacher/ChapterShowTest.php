<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapterShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_shows_only_the_signed_in_teachers_content_in_the_chapter(): void
    {
        $grade = Grade::factory()->level(4)->create();
        $subject = Subject::factory()->availableIn($grade)->create();
        $chapter = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);

        $mine = User::factory()->teacher()->create();
        $other = User::factory()->teacher()->create();

        $myLesson = Lesson::factory()->for($chapter)->create(['teacher_id' => $mine->id]);
        Material::factory()->for($chapter)->create(['teacher_id' => $mine->id]);
        Quiz::factory()->for($chapter)->create(['teacher_id' => $mine->id]);

        $theirLesson = Lesson::factory()->for($chapter)->create(['teacher_id' => $other->id]);

        $response = $this->actingAs($mine)->get(route('cikgu.bab.show', $chapter));

        $response->assertOk();
        $this->assertEquals([$myLesson->id], $response->viewData('lessons')->pluck('id')->all());
        $this->assertCount(1, $response->viewData('materials'));
        $this->assertCount(1, $response->viewData('quizzes'));
        $this->assertFalse($response->viewData('lessons')->contains('id', $theirLesson->id));
    }
}
