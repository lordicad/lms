<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizFilterAndTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_chapter_filter_returns_only_quizzes_in_that_chapter(): void
    {
        $grade = Grade::factory()->level(5)->create();
        $subject = Subject::factory()->availableIn($grade)->create();
        $chapterA = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);
        $chapterB = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);

        $teacher = User::factory()->teacher()->create();
        $quizA = Quiz::factory()->for($chapterA)->create(['teacher_id' => $teacher->id]);
        Quiz::factory()->for($chapterB)->create(['teacher_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->get(route('cikgu.kuiz.index', [
            'tahun' => 5, 'subjek' => $subject->slug, 'bab' => $chapterA->id,
        ]));

        $response->assertOk();
        $this->assertEquals([$quizA->id], $response->viewData('quizzes')->pluck('id')->all());
    }

    public function test_totals_reflect_all_owned_records_and_filtered_count_is_separate(): void
    {
        $grade = Grade::factory()->level(6)->create();
        $subject = Subject::factory()->availableIn($grade)->create();
        $chapter = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);
        $otherChapter = Chapter::factory()->create();

        $teacher = User::factory()->teacher()->create();
        Quiz::factory()->count(3)->for($chapter)->create(['teacher_id' => $teacher->id]);
        Quiz::factory()->count(2)->for($otherChapter)->create(['teacher_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->get(route('cikgu.kuiz.index', [
            'tahun' => 6, 'subjek' => $subject->slug,
        ]));

        $response->assertOk();
        $this->assertSame(5, $response->viewData('totalQuizzes'));
        $this->assertSame(3, $response->viewData('filteredCount'));
    }

    public function test_a_tampered_chapter_from_another_subject_is_ignored(): void
    {
        $grade = Grade::factory()->level(2)->create();
        $subject = Subject::factory()->availableIn($grade)->create();
        $chapter = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);

        // A chapter that belongs to a different subject entirely.
        $foreignChapter = Chapter::factory()->create();

        $teacher = User::factory()->teacher()->create();
        Quiz::factory()->for($chapter)->create(['teacher_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->get(route('cikgu.kuiz.index', [
            'tahun' => 2, 'subjek' => $subject->slug, 'bab' => $foreignChapter->id,
        ]));

        $response->assertOk();
        // The tampered Bab resolves to nothing, so it does not constrain by that chapter — the
        // Subject+Year filter still applies and returns the subject's quizzes safely.
        $this->assertNull($response->viewData('filter')->chapter);
    }
}
