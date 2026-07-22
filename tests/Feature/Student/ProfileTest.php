<?php

namespace Tests\Feature\Student;

use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A student changes their display name and photo. Their legal name, school, year, class and
 * guardian contacts are the school's record, kept by the admin.
 *
 * As with the teacher profile, hiding the inputs is not the control — these post the locked fields
 * through the route and check the database afterwards.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        $grade = Grade::factory()->level(3)->create();
        $school = School::factory()->create(['name' => 'SK Bukit Damansara']);

        return User::factory()->student(3)->create([
            'name' => 'Aish Irfan',
            'username' => 'Aish',
            'school_id' => $school->id,
            'grade_id' => $grade->id,
            'guardian_name' => 'Encik Rahman',
            'guardian_email' => 'rahman@example.com',
        ]);
    }

    public function test_profile_page_renders_for_a_student(): void
    {
        $this->actingAs($this->student())->get(route('profile.edit'))->assertOk();
    }

    /** A new account has no school, class or guardian details for the page to show. */
    public function test_profile_page_renders_before_the_admin_has_filled_anything_in(): void
    {
        Grade::factory()->level(5)->create();
        $student = User::factory()->student(5)->create([
            'school_id' => null,
            'guardian_name' => null,
            'guardian_phone' => null,
            'guardian_email' => null,
        ]);

        $this->actingAs($student)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(__('Belum ditetapkan'));
    }

    public function test_a_student_can_change_their_username(): void
    {
        $student = $this->student();

        $this->actingAs($student)
            ->patch(route('profile.update'), ['username' => 'Aish Baharu'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Aish Baharu', $student->fresh()->username);
    }

    public function test_a_posted_name_school_or_year_is_ignored(): void
    {
        $student = $this->student();
        $otherSchool = School::factory()->create(['name' => 'SK Taman Tun']);
        $otherGrade = Grade::factory()->level(6)->create();

        $this->actingAs($student)->patch(route('profile.update'), [
            'username' => 'Aish',
            'name' => 'Someone Else',
            'school_id' => $otherSchool->id,
            'grade_level' => $otherGrade->level,
        ])->assertRedirect();

        $student->refresh();

        $this->assertSame('Aish Irfan', $student->name);
        $this->assertSame('SK Bukit Damansara', $student->school->name);
        $this->assertSame(3, $student->grade->level);
    }

    public function test_a_posted_class_or_guardian_detail_is_ignored(): void
    {
        $student = $this->student();
        $class = SchoolClass::factory()->for($student->school)
            ->state(['grade_id' => $student->grade_id])->create();

        $this->actingAs($student)->patch(route('profile.update'), [
            'username' => 'Aish',
            'school_class_id' => $class->id,
            'guardian_name' => 'Orang Lain',
            'guardian_email' => 'lain@example.com',
        ])->assertRedirect();

        $student->refresh();

        $this->assertNull($student->school_class_id, 'a student must not put themselves in a class');
        $this->assertSame('Encik Rahman', $student->guardian_name);
        $this->assertSame('rahman@example.com', $student->guardian_email);
    }

    public function test_the_form_offers_no_input_for_the_locked_fields(): void
    {
        $html = $this->actingAs($this->student())->get(route('profile.edit'))->getContent();

        foreach (['name', 'school_id', 'grade_level', 'school_class_id', 'guardian_name', 'guardian_phone', 'guardian_email'] as $field) {
            $this->assertDoesNotMatchRegularExpression(
                '/<(input|select|textarea)[^>]*name="'.preg_quote($field, '/').'"/',
                $html,
                "the student profile still submits a {$field} field",
            );
        }

        // Readable, just not editable.
        $this->assertStringContainsString('Aish Irfan', $html);
        $this->assertStringContainsString('SK Bukit Damansara', $html);
        $this->assertStringContainsString('Encik Rahman', $html);
    }

    /** The mobile endpoint is the same form and must not be a way around the lock. */
    public function test_the_mobile_endpoint_locks_the_same_fields(): void
    {
        $student = $this->student();
        $otherSchool = School::factory()->create(['name' => 'SK Taman Tun']);

        $this->actingAs($student, 'sanctum')->patchJson('/api/auth/profile', [
            'username' => 'Aish',
            'name' => 'Someone Else',
            'school_id' => $otherSchool->id,
            'guardian_name' => 'Orang Lain',
        ])->assertOk();

        $student->refresh();

        $this->assertSame('Aish Irfan', $student->name);
        $this->assertSame('SK Bukit Damansara', $student->school->name);
        $this->assertSame('Encik Rahman', $student->guardian_name);
    }
}
