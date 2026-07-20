<?php

namespace Tests\Feature\Student;

use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_renders_for_a_student(): void
    {
        Grade::factory()->level(3)->create();
        $student = User::factory()->student(3)->create();

        $this->actingAs($student)->get(route('profile.edit'))->assertOk();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nur Aisyah',
            'username' => 'aisyah',
            'grade_level' => 3,
        ], $overrides);
    }

    public function test_student_saves_school_class_and_guardian_details(): void
    {
        $grade = Grade::factory()->level(3)->create();
        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->state(['grade_id' => $grade->id])->create();
        $student = User::factory()->student(3)->create();

        $this->actingAs($student)->patch(route('profile.update'), $this->payload([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'guardian_name' => 'Encik Rahman',
            'guardian_phone' => '+60 19-876 5432',
            'guardian_email' => 'rahman@example.com',
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame($school->id, $student->school_id);
        $this->assertSame($class->id, $student->school_class_id);
        $this->assertSame('Encik Rahman', $student->guardian_name);
        $this->assertSame('rahman@example.com', $student->guardian_email);
    }

    public function test_class_must_match_the_selected_school_and_year(): void
    {
        $grade3 = Grade::factory()->level(3)->create();
        $grade4 = Grade::factory()->level(4)->create();
        $school = School::factory()->create();
        // A class that is Tahun 4, but the student submits Tahun 3.
        $wrongYearClass = SchoolClass::factory()->for($school)->state(['grade_id' => $grade4->id])->create();
        $student = User::factory()->student(3)->create();

        $this->actingAs($student)->patch(route('profile.update'), $this->payload([
            'grade_level' => 3,
            'school_id' => $school->id,
            'school_class_id' => $wrongYearClass->id,
        ]))->assertSessionHasErrors('school_class_id');
    }

    public function test_guardian_email_is_validated(): void
    {
        Grade::factory()->level(3)->create();
        $student = User::factory()->student(3)->create();

        $this->actingAs($student)->patch(route('profile.update'), $this->payload([
            'guardian_email' => 'not-an-email',
        ]))->assertSessionHasErrors('guardian_email');
    }
}
