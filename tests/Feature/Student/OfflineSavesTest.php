<?php

namespace Tests\Feature\Student;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineSavesTest extends TestCase
{
    use RefreshDatabase;

    public function test_year_subject_filter_applies_to_videos_and_materials(): void
    {
        $grade = Grade::factory()->level(4)->create();
        $maths = Subject::factory()->availableIn($grade)->create();
        $science = Subject::factory()->availableIn($grade)->create();

        $mChapter = Chapter::factory()->create(['subject_id' => $maths->id, 'grade_id' => $grade->id]);
        $sChapter = Chapter::factory()->create(['subject_id' => $science->id, 'grade_id' => $grade->id]);

        $teacher = User::factory()->teacher()->create();
        $mLesson = Lesson::factory()->for($mChapter)->create(['teacher_id' => $teacher->id]);
        Lesson::factory()->for($sChapter)->create(['teacher_id' => $teacher->id]);
        Material::factory()->for($mChapter)->create(['teacher_id' => $teacher->id]);
        Material::factory()->for($sChapter)->create(['teacher_id' => $teacher->id]);

        $student = User::factory()->student(4)->create();

        $response = $this->actingAs($student)->get(route('simpanan.index', [
            'tahun' => 4, 'subjek' => $maths->slug,
        ]));

        $response->assertOk();
        $this->assertEquals([$mLesson->id], $response->viewData('lessons')->pluck('id')->all());
        // Materials are grouped by chapter; only the maths chapter should be present.
        $this->assertEquals([$mChapter->id], $response->viewData('materialsByChapter')->keys()->all());
    }

    public function test_each_item_shows_the_teacher_label_without_private_information(): void
    {
        $grade = Grade::factory()->level(5)->create();
        $subject = Subject::factory()->availableIn($grade)->create();
        $chapter = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id]);

        $teacher = User::factory()->teacher()->create([
            'name' => 'Cikgu Rahim', 'phone' => '0123456789', 'email' => 'rahim@moe.gov.my',
        ]);
        Lesson::factory()->for($chapter)->create(['teacher_id' => $teacher->id]);

        $student = User::factory()->student(5)->create();

        $response = $this->actingAs($student)->get(route('simpanan.index', ['tahun' => 5]));

        $response->assertOk();
        $response->assertSee('Cikgu Rahim');
        $response->assertDontSee('0123456789');
        $response->assertDontSee('rahim@moe.gov.my');
    }

    public function test_defaults_to_the_students_own_year_without_a_query(): void
    {
        $grade = Grade::factory()->level(2)->create();
        $student = User::factory()->student(2)->create();

        $response = $this->actingAs($student)->get(route('simpanan.index'));

        $response->assertOk();
        $this->assertSame($grade->id, $response->viewData('grade')->id);
    }
}
