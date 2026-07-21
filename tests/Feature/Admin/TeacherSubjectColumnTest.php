<?php

namespace Tests\Feature\Admin;

use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherSubjectColumnTest extends TestCase
{
    use RefreshDatabase;

    /** The case from the screenshot: assigned a subject, nothing posted yet. */
    public function test_a_teacher_with_no_content_still_shows_their_assigned_subject(): void
    {
        $subject = Subject::factory()->create(['name' => 'Bahasa Melayu']);
        $teacher = User::factory()->teacher()->create(['name' => 'Aminah Abdullah']);
        $teacher->subjects()->sync([$subject->id]);

        $byTeacher = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.bakat'))
            ->assertOk()
            ->viewData('subjectsByTeacher');

        $this->assertEqualsCanonicalizing([$subject->id], $byTeacher[$teacher->id]->all());
    }

    public function test_posted_content_and_assigned_subjects_are_combined(): void
    {
        $posted = Subject::factory()->create(['name' => 'Sains']);
        $assigned = Subject::factory()->create(['name' => 'Matematik']);

        $teacher = User::factory()->teacher()->create();
        $teacher->subjects()->sync([$assigned->id]);

        $chapter = Chapter::factory()->create(['subject_id' => $posted->id]);
        Lesson::factory()->for($chapter)->create(['teacher_id' => $teacher->id]);

        $byTeacher = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.bakat'))
            ->viewData('subjectsByTeacher');

        $this->assertEqualsCanonicalizing([$posted->id, $assigned->id], $byTeacher[$teacher->id]->all());
    }

    public function test_a_teacher_with_neither_still_shows_nothing(): void
    {
        $teacher = User::factory()->teacher()->create();

        $byTeacher = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.bakat'))
            ->viewData('subjectsByTeacher');

        $this->assertTrue(($byTeacher[$teacher->id] ?? collect())->isEmpty());
    }
}
