<?php

namespace Tests\Feature\Foundation;

use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_teach_many_subjects(): void
    {
        $teacher = User::factory()->teacher()->create();
        $a = Subject::factory()->create();
        $b = Subject::factory()->create();

        $teacher->subjects()->sync([$a->id, $b->id]);

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $teacher->subjects->pluck('id')->all());
        $this->assertTrue($a->teachers->contains($teacher));
    }

    public function test_class_belongs_to_a_school_and_a_grade(): void
    {
        $grade = Grade::factory()->level(6)->create();
        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->state(['grade_id' => $grade->id])->create();

        $this->assertTrue($class->school->is($school));
        $this->assertTrue($class->grade->is($grade));
        $this->assertSame('Tahun 6 '.$class->name, $class->label());
    }

    public function test_student_homeroom_teacher_is_derived_from_class(): void
    {
        $grade = Grade::factory()->level(5)->create();
        $school = School::factory()->create();
        $homeroom = User::factory()->teacher()->create();
        $class = SchoolClass::factory()->for($school)->state([
            'grade_id' => $grade->id,
            'homeroom_teacher_id' => $homeroom->id,
        ])->create();

        $student = User::factory()->inClass($class)->create(['role' => User::ROLE_STUDENT]);

        $this->assertTrue($student->homeroomTeacher()->is($homeroom));
        $this->assertTrue($homeroom->homeroomClass->is($class));
    }

    public function test_a_teacher_is_homeroom_of_at_most_one_class(): void
    {
        $teacher = User::factory()->teacher()->create();
        SchoolClass::factory()->create(['homeroom_teacher_id' => $teacher->id]);

        $this->expectException(QueryException::class);
        SchoolClass::factory()->create(['homeroom_teacher_id' => $teacher->id]);
    }
}
