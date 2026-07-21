<?php

namespace Tests\Feature\Teacher;

use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_renders_for_a_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();
        School::factory()->create();

        $this->actingAs($teacher)->get(route('profile.edit'))->assertOk();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Cikgu Aisyah',
            'username' => 'aisyah',
            'email' => 'aisyah@moe.gov.my',
        ], $overrides);
    }

    public function test_teacher_saves_phone_position_school_and_multiple_subjects(): void
    {
        $teacher = User::factory()->teacher()->create();
        $school = School::factory()->create();
        $maths = Subject::factory()->create();
        $science = Subject::factory()->create();

        $this->actingAs($teacher)->patch(route('profile.update'), $this->payload([
            'phone' => '+60 12-345 6789',
            'position' => 'Guru Kanan',
            'school_id' => $school->id,
            'subjects' => [$maths->id, $science->id],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $teacher->refresh();
        $this->assertSame('Guru Kanan', $teacher->position);
        $this->assertSame($school->id, $teacher->school_id);
        $this->assertEqualsCanonicalizing([$maths->id, $science->id], $teacher->subjects->pluck('id')->all());
    }

    public function test_teacher_becomes_homeroom_of_a_class_in_their_school(): void
    {
        $grade = Grade::factory()->level(6)->create();
        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->state(['grade_id' => $grade->id])->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->patch(route('profile.update'), $this->payload([
            'school_id' => $school->id,
            'homeroom_class_id' => $class->id,
        ]))->assertRedirect();

        $this->assertSame($teacher->id, $class->fresh()->homeroom_teacher_id);
    }

    public function test_homeroom_class_must_be_in_the_selected_school(): void
    {
        $otherSchool = School::factory()->create();
        $mySchool = School::factory()->create();
        $classElsewhere = SchoolClass::factory()->for($otherSchool)->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->patch(route('profile.update'), $this->payload([
            'school_id' => $mySchool->id,
            'homeroom_class_id' => $classElsewhere->id,
        ]))->assertSessionHasErrors('homeroom_class_id');
    }
}
